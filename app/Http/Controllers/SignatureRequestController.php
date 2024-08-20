<?php

namespace App\Http\Controllers;

use App\Jobs\SignDocumentJob;
use App\Models\Document;
use App\Models\SignatureRequest;
use App\Models\User;
use App\Services\CredentialsDTO;
use App\Services\SignatureRequestStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Annotations as OA;

/**
 * @OA\Info(title="E-Signature API", version="1.0.0")
 */
class SignatureRequestController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/signature-requests/{uuid}",
     *     summary="Get a specific signature request",
     *     tags={"Signature Requests"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the signature request",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/SignatureRequest")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function show(SignatureRequest $signatureRequest)
    {
        $currentUserId = Auth::id();

        if (!in_array($currentUserId, [$signatureRequest->requester_id, $signatureRequest->signer_id])) {
            return response()->json(['error' => "You don't have access to this document"], 403);
        }

        return response()->json($signatureRequest->load(['document']));
    }

    /**
     * @OA\Get(
     *     path="/api/signature-requests",
     *     summary="Get all signature requests",
     *     tags={"Signature Requests"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/SignatureRequest")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function all()
    {
        $currentUserId = Auth::id();

        return response()->json(
            SignatureRequest::where('signer_id', $currentUserId)
                ->orWhere('requester_id', $currentUserId)
                ->with('document')
                ->get()
        );
    }

    /**
     * @OA\Post(
     *     path="/api/signature-requests",
     *     summary="Create a new signature request",
     *     tags={"Signature Requests"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"document_id", "signer_id"},
     *             @OA\Property(property="document_id", type="string", description="ID of the document to be signed"),
     *             @OA\Property(property="signer_id", type="string", description="ID of the user who will sign the document")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Signature request created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/SignatureRequest")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function create(Request $request)
    {
        $validated = $request->validate([
            'document_id' => 'required|exists:documents,id',
            'signer_email' => 'required|exists:users,email',
        ]);

        /** @var Document $document */
        $document = Document::find($validated['document_id']);

        if ($document->signed_at) {
            return response()->json(['error' => 'Document is already signed'], 400);
        }

        foreach ($document->signatureRequests()->get() as $documentRequest) {
            if ($documentRequest->status !== SignatureRequestStatus::Error->value) {
                return response()->json(['error' => 'Request already exists'], 400);
            }
        }

        $signer = User::where('email', $validated['signer_email'])->first();

        $signatureRequest = SignatureRequest::create([
            'document_id' => $validated['document_id'],
            'requester_id' => Auth::id(),
            'signer_id' => $signer->id,
            'status' => SignatureRequestStatus::Pending,
        ]);

        return response()->json($signatureRequest, 201);
    }

    /**
     * @OA\Post(
     *     path="/api/signature-requests/{uuid}/sign",
     *     summary="Sign a signature request",
     *     tags={"Signature Requests"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="UUID of the signature request to be signed",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"key", "cert"},
     *             @OA\Property(property="key", type="string", description="Private key for signing"),
     *             @OA\Property(property="cert", type="string", description="Certificate for signing")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Document signed successfully",
     *         @OA\JsonContent(ref="#/components/schemas/SignatureRequest")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request (e.g., document already signed)"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Signature request not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function sign(SignatureRequest $signatureRequest, Request $request)
    {
        // just for example, obviously in the real App key and cert should be files
        $validated = $request->validate([
            'key' => 'required|string:255',
            'cert' => 'required|string:255',
        ]);

        if ($signatureRequest->signer_id !== Auth::id()) {
            return response()->json(['error' => "You don't have access to this document"], 403);
        }

        if ($signatureRequest->status === SignatureRequestStatus::Signed->value) {
            return response()->json(['error' => 'Document is already signed'], 400);
        }

        $signatureRequest->update(['status' => SignatureRequestStatus::PendingSigning]);

        SignDocumentJob::dispatch($signatureRequest, new CredentialsDTO($validated['key'], $validated['cert']));

        return response()->json($signatureRequest, 202);
    }

    /**
     * @OA\Post(
     *     path="/api/signature-requests/{uuid}/deny",
     *     summary="Deny a signature request",
     *     tags={"Signature Requests"},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         description="UUID of the signature request to be denied",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Signature request denied successfully",
     *         @OA\JsonContent(ref="#/components/schemas/SignatureRequest")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Signature request not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function deny(SignatureRequest $signatureRequest)
    {
        if ($signatureRequest->signer_id !== Auth::id()) {
            return response()->json(['error' => "You don't have access to this document"], 403);
        }

        if ($signatureRequest->status !== SignatureRequestStatus::Pending->value) {
            return response()->json(['error' => 'Could not deny request'], 400);
        }

        $signatureRequest->update(['status' => SignatureRequestStatus::Denied]);

        return response()->json($signatureRequest);
    }

    /**
     * @OA\Delete(
     *     path="/api/signature-requests/{uuid}",
     *     summary="Delete a signature request",
     *     tags={"Signature Requests"},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         description="UUID of the signature request to be deleted",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Signature request deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Signature request not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function delete(SignatureRequest $signatureRequest)
    {
        if ($signatureRequest->requester_id !== Auth::id()) {
            return response()->json(['error' => "You don't have access to this document"], 403);
        }

        if ($signatureRequest->status !== SignatureRequestStatus::Pending->value) {
            return response()->json(['error' => 'Could not delete request'], 400);
        }

        $signatureRequest->update(['status' => SignatureRequestStatus::Deleted]);

        return response()->json($signatureRequest);
    }
}
