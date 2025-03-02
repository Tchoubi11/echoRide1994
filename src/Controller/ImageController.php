<?php


namespace App\Controller;

use App\Entity\Image;
use App\Entity\Utilisateur;
use App\Form\ImageType;
use App\Repository\ImageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;

class ImageController extends AbstractController
{
    #[Route('/upload-image/{type}', name: 'image_upload')]
    public function upload(Request $request, EntityManagerInterface $entityManager, string $type = 'other'): Response
    {
        // Répertoire d'upload
        $imageDirectory = $this->getParameter('images_directory');
        $image = new Image();
        $form = $this->createForm(ImageType::class, $image);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                // Vérification de l'extension, du type MIME et de la taille
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                $extension = strtolower($imageFile->guessExtension());
                if (!in_array($extension, $allowedExtensions)) {
                    $this->addFlash('error', 'Format de fichier non autorisé (JPEG, PNG, GIF uniquement).');
                    return $this->redirectToRoute('image_upload', ['type' => $type]);
                }

                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($imageFile->getMimeType(), $allowedMimeTypes)) {
                    $this->addFlash('error', 'Type MIME non autorisé.');
                    return $this->redirectToRoute('image_upload', ['type' => $type]);
                }

                $maxSize = 5 * 1024 * 1024; 
                if ($imageFile->getSize() > $maxSize) {
                    $this->addFlash('error', 'Le fichier est trop volumineux (max: 5 Mo).');
                    return $this->redirectToRoute('image_upload', ['type' => $type]);
                }

                // Génération d' un nom unique
                $newFilename = uniqid() . '.' . $extension;

                // Déplacecement du fichier dans le répertoire d'upload
                try {
                    $imageFile->move($imageDirectory, $newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement : ' . $e->getMessage());
                    return $this->redirectToRoute('image_upload', ['type' => $type]);
                }

                // Enregistrement de l'image dans la base de données
                $image->setImagePath($newFilename);
                $entityManager->persist($image);

                // Logique pour les photos de profil
                if ($type === 'profil') {
                    // Si c'est une photo de profil,on associe l'image à l'utilisateur connecté
                    $user = $this->getUser();
                    if ($user instanceof Utilisateur) {  
                        $user->setPhoto($image); 
                        $entityManager->persist($user);
                    } else {
                        $this->addFlash('error', 'Utilisateur non trouvé ou non valide.');
                        return $this->redirectToRoute('image_upload');
                    }
                }

                // Enregistrement des modifications
                $entityManager->flush();

                // On retourne un message de succès
                $this->addFlash('success', 'Image téléchargée avec succès !');
                return $this->redirectToRoute('image_list');
            }
        }

        return $this->render('image/upload.html.twig', [
            'form' => $form->createView(),
            'type' => $type, 
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
