<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Annotations as OA;

class DocumentController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/documents/upload",
     *     summary="Upload a new document",
     *     tags={"Documents"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"document"},
     *                 @OA\Property(
     *                     property="document",
     *                     description="The PDF document file to upload",
     *                     type="string",
     *                     format="binary"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Document uploaded successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Document")
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
    public function upload(Request $request)
    {
        $request->validate([
            'document' => 'required|mimes:pdf|max:10240',
        ]);

        $path = $request->file('document')->store('documents');

        $document = Document::create([
            'file_path' => $path,
            'user_id' => Auth::id(),
        ]);

        return response()->json($document, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/documents",
     *     summary="Get all documents for the current user",
     *     tags={"Documents"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Document")
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
        return response()->json(Document::where('user_id', Auth::id())->get());
    }

    /**
     * @OA\Get(
     *     path="/api/documents/{uuid}",
     *     summary="Get a specific document by UUID",
     *     tags={"Documents"},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         description="UUID of the document to retrieve",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Document")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Document not found"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function show(Document $document)
    {
        if ($document->user_id !== Auth::id()) {
            return response()->json(['error' => "You don't have access to this document"], 403);
        }

        return response()->json($document);
    }
}
