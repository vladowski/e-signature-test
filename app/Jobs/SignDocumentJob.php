<?php

namespace App\Jobs;

use App\Models\SignatureRequest;
use App\Services\CredentialsDTO;
use App\Services\SignatureRequestStatus;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SignDocumentJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private SignatureRequest $signatureRequest,
        private CredentialsDTO $credentialsDTO
    )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Simulate signing the document using an abstract service
            $this->signDocument();
        } catch (Exception $e) {
            $this->signatureRequest->update(['status' => SignatureRequestStatus::Error]);
        }
    }

    /**
     * Should be implemented in the real App
     * For example, by PyPDF2 python lib
     *
     * @return void
     * @throws Exception
     */
    private function signDocument(): void
    {
        if ($this->signatureRequest->status !== SignatureRequestStatus::PendingSigning->value) {
            throw new Exception('Document sign error');
        }

        // just for testing purposes
        if (rand(1, 100) <= 33) {
            throw new Exception('Document sign error');
        }

        $this->signatureRequest->update([
            'status' => SignatureRequestStatus::Signed,
        ]);

        $this->signatureRequest->document()->update([
            'signed_file_path' => $this->signatureRequest->document()->file_path,
            'signed_at' => new \DateTime()
        ]);
    }
}
