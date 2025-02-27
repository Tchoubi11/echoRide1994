<?php

namespace App\Controller;

use App\Entity\Image;
use App\Form\ImageType;
use App\Repository\ImageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;

class ImageController extends AbstractController
{
    #[Route('/upload-image', name: 'image_upload')]
    public function upload(Request $request, EntityManagerInterface $entityManager): Response
    {
        // ici je récupére le répertoire d'upload depuis les paramètres
        $imageDirectory = $this->getParameter('images_directory');
    
        $image = new Image();
        $form = $this->createForm(ImageType::class, $image);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('imageFile')->getData();
    
            if ($imageFile) {
                // ensuite je vérifie l'extension
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                $extension = strtolower($imageFile->guessExtension());
                if (!in_array($extension, $allowedExtensions)) {
                    $this->addFlash('error', 'Format de fichier non autorisé (JPEG, PNG, GIF uniquement).');
                    return $this->redirectToRoute('image_upload');
                }
    
                // ic je vérifie le type MIME
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($imageFile->getMimeType(), $allowedMimeTypes)) {
                    $this->addFlash('error', 'Type MIME non autorisé.');
                    return $this->redirectToRoute('image_upload');
                }
    
                // puis je vérifie de la taille maximale (5 Mo)
                $maxSize = 5 * 1024 * 1024; 
                if ($imageFile->getSize() > $maxSize) {
                    $this->addFlash('error', 'Le fichier est trop volumineux (max: 5 Mo).');
                    return $this->redirectToRoute('image_upload');
                }
    
                // je génère un nom unique
                $newFilename = uniqid() . '.' . $extension;
    
                // je déplace le fichier dans le répertoire d'upload
                try {
                    $imageFile->move($imageDirectory, $newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement : ' . $e->getMessage());
                    return $this->redirectToRoute('image_upload');
                }
    
                // enfin j'enregistre en base de données
                $image->setImagePath($newFilename);
                $entityManager->persist($image);
                $entityManager->flush();
    
                $this->addFlash('success', 'Image téléchargée avec succès !');
    
                return $this->redirectToRoute('image_list');
            }
        }
    
        return $this->render('image/upload.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    

    #[Route('/images', name: 'image_list')]
    public function list(ImageRepository $imageRepository): Response
    {
        return $this->render('image/list.html.twig', [
            'images' => $imageRepository->findAll(),
        ]);
    }
}
