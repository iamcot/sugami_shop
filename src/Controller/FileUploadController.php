<?php

namespace App\Controller;

use App\Service\AwsService;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;
use Symfony\Component\Routing\Attribute\Route;


class FileUploadController extends AbstractController
{
    #[Route('/api/uploads3', name: 'app_file_upload', methods: ['POST'])]
    public function uploadS3(
        #[MapUploadedFile] UploadedFile $video,
        AwsService $awsService,
        LoggerInterface $log,
    ): JsonResponse {
        if ($video->isValid()) {
            try {
                $uploaded = $awsService->doUpload($video);
                $fileName = $video->getClientOriginalName();
                return $this->json([
                    'message' => "Uploaded file $fileName !",
                    'result' => true
                ]);
            } catch (Exception $e) {
                $log->error($e);
            }
            return  $this->json([
                'message' => "Upload file FAILED !",
                'result' => false
            ]);
        } 
        return  $this->json([
            'message' => "File does not EXISTS!",
            'result' => false
        ]);
        
        
    }

    #[Route('/api/files', name: 'app_get_files')]
    public function getFiles(Request $request,
    AwsService $awsService,
    ): JsonResponse {
        $files = $awsService->getFiles();
        return $this->json([
            'result' => true,
            'data' => $files
        ]);
    }

    #[Route('/api/file/{packCode}', name: 'app_get_file')]
    public function getFile(Request $request, string $packCode,
    AwsService $awsService,
    ): JsonResponse {
        $file = $awsService->getFile($packCode);
        return $this->json([
            'result' => true,
            'data' => $file
        ]);
    }
}
