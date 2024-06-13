<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

class ImageController extends AbstractController
{
    #[Route('/uploads/images/{filename}', name: 'image_display')]
    public function displayImage($filename)
    {
        $path = $this->getParameter('images_directory') . '/' . $filename;

        return new BinaryFileResponse($path);
    }
}
