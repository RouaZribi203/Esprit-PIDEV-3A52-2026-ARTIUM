<?php

namespace App\Controller;

use App\Entity\Oeuvre;
use App\Entity\User;
use App\Enum\Specialite;
use App\Enum\TypeOeuvre;
use App\Form\OeuvreType;
use App\Repository\OeuvreRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;


final class OeuvreFrontController extends AbstractController
{
    #[Route('/mes_oeuvres', name: 'app_oeuvre_front')]
    public function index(OeuvreRepository $oeuvreRepository, UserRepository $userRepository): Response
    {
        $user = $this->getUser();
        $oeuvre = new Oeuvre();
        $oeuvres = [];
        if ($user && method_exists($user, 'getCollections')) {
            foreach ($user->getCollections() as $collection) {
                if (method_exists($collection, 'getOeuvres')) {
                    foreach ($collection->getOeuvres() as $oeuvreItem) {
                        $oeuvres[] = $oeuvreItem;
                    }
                }
            }
        }
        $processedOeuvres = [];
        $form = $this->createForm(OeuvreType::class, $oeuvre, [
            'include_date' => false,
            'user' => $user,
            'include_type' => false,
        ]);
        foreach ($oeuvres as $oeuvre) {
            $image = $oeuvre->getImage();
            if ($image) {
                if (is_resource($image)) {
                    rewind($image);
                    $imageData = stream_get_contents($image);
                } else {
                    $imageData = $image;
                }
                if ($imageData && strlen($imageData) > 0) {
                    $imageBase64 = base64_encode($imageData);
                    $finfo = new \finfo(FILEINFO_MIME_TYPE);
                    $mimeType = $finfo->buffer($imageData);
                    $processedOeuvres[$oeuvre->getId()] = [
                        'imageBase64' => $imageBase64,
                        'mimeType' => $mimeType ?: 'image/jpeg',
                    ];
                }
            }
        }
        return $this->render('oeuvre_front/oeuvre_front.html.twig', [
            'controller_name' => 'OeuvreFrontController',
            'form' => $form->createView(),
            'oeuvres' => $oeuvres,
            'typeOeuvres' => TypeOeuvre::cases(),
            'processedOeuvres' => $processedOeuvres,
        ]);
    }
    #[Route('/new_oeuvre', name: 'app_oeuvre_new', methods: ['GET','POST'])]
    public function new(Request $request,EntityManagerInterface $entityManager,UserRepository $userRepository): Response {
        
        $oeuvre = new Oeuvre();
        $user = $this->getUser();
        $session = $request->getSession();
        $tempImageName = $session->get('oeuvre_temp_image');
        $form = $this->createForm(OeuvreType::class, $oeuvre, [
            'include_date' => false,
            'user' => $user,
            'temp_image_present' => $tempImageName !== null,
            'include_type' => false,
            'validation_groups' => ['Default'],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $autoType = $this->resolveTypeFromUserSpecialite($user instanceof User ? $user : null);
            if ($autoType === null) {
                $form->addError(new FormError('Impossible de déterminer le type automatiquement. Vérifiez votre spécialité (Peintre, Photographe ou Sculpteur).'));
            } else {
                $oeuvre->setType($autoType);
            }
        }

        $tempDir = $this->getParameter('kernel.project_dir') . '/var/tmp/oeuvre_uploads';
        $imageFile = $form->get('image')->getData();
        if ($imageFile instanceof UploadedFile) {
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }
            $newName = bin2hex(random_bytes(16));
            $ext = $imageFile->guessExtension();
            if ($ext) {
                $newName .= '.' . $ext;
            }

            $imageFile->move($tempDir, $newName);

            if ($tempImageName) {
                $oldPath = $tempDir . '/' . $tempImageName;
                if (is_file($oldPath)) {
                    unlink($oldPath);
                }
            }

            $tempImageName = $newName;
            $session->set('oeuvre_temp_image', $tempImageName);
        }

    if ($form->isSubmitted() && $form->isValid()) {

        $tempPath = $tempImageName ? $tempDir . '/' . $tempImageName : null;
        if ($tempPath && is_file($tempPath)) {
            $blobData = fopen($tempPath, 'rb');
            $oeuvre->setImage($blobData);
            unlink($tempPath);
            $session->remove('oeuvre_temp_image');
        } elseif ($imageFile) {
            // Read the binary content of the file
            $blobData = fopen($imageFile->getPathname(), 'rb');
            $oeuvre->setImage($blobData);
        }
        $oeuvre->setDateCreation(new \DateTime());
        $entityManager->persist($oeuvre);
        $entityManager->flush();

        $this->addFlash('success', 'Œuvre ajoutée avec succès 🎨');

        return $this->redirectToRoute('app_oeuvre_front');
       }

       $user = $this->getUser();
       $oeuvres = [];
       if ($user && method_exists($user, 'getCollections')) {
           foreach ($user->getCollections() as $collection) {
               if (method_exists($collection, 'getOeuvres')) {
                   foreach ($collection->getOeuvres() as $oeuvreItem) {
                       $oeuvres[] = $oeuvreItem;
                   }
               }
           }
       }
       $processedOeuvres = [];
       foreach ($oeuvres as $oeuvre) {
           $image = $oeuvre->getImage();
           if ($image) {
               if (is_resource($image)) {
                   rewind($image);
                   $imageData = stream_get_contents($image);
               } else {
                   $imageData = $image;
               }
               if ($imageData && strlen($imageData) > 0) {
                   $imageBase64 = base64_encode($imageData);
                   $finfo = new \finfo(FILEINFO_MIME_TYPE);
                   $mimeType = $finfo->buffer($imageData);
                   $processedOeuvres[$oeuvre->getId()] = [
                       'imageBase64' => $imageBase64,
                       'mimeType' => $mimeType ?: 'image/jpeg',
                   ];
               }
           }
       }
       return $this->render('oeuvre_front/oeuvre_front.html.twig', [
           'form' => $form->createView(),
           'oeuvres' => $oeuvres,
           'typeOeuvres' => TypeOeuvre::cases(),
           'processedOeuvres' => $processedOeuvres,
           'tempImagePresent' => $tempImageName !== null,
            'tempImageName' => $tempImageName,
       ]);
    }

    private function resolveTypeFromUserSpecialite(?User $user): ?TypeOeuvre
    {
        return match ($user?->getSpecialite()) {
            Specialite::PEINTRE => TypeOeuvre::PEINTURE,
            Specialite::PHOTOGRAPHE => TypeOeuvre::PHOTOGRAPHIE,
            Specialite::SCULPTEUR => TypeOeuvre::SCULPTURE,
            default => null,
        };
    }
    #[Route('/mes_oeuvres/{id}/delete', name: 'app_oeuvre_delete_front', methods: ['POST'])]
    public function delete(Request $request, Oeuvre $oeuvre, EntityManagerInterface $em): Response
    {
        $likes = $oeuvre->getLikes(); 
        foreach ($likes as $like) {$em->remove($like);}
        if ($this->isCsrfTokenValid('delete'.$oeuvre->getId(),$request->getPayload()->getString('_token'))) {
        $em->remove($oeuvre);
        $em->flush();
        }

        return $this->redirectToRoute('app_oeuvre_front');
    }


    #[Route('/{id}/edit_oeuvre', name: 'oeuvre_edit', methods: ['GET','POST'])]
    public function edit(Request $request, Oeuvre $oeuvre, EntityManagerInterface $entityManager, UserRepository $userRepository, OeuvreRepository $oeuvreRepository): Response {
        
        $user = $this->getUser();
        $form = $this->createForm(OeuvreType::class, $oeuvre, [
            'image_required' => false,
            'user' => $user,
            'include_type' => false,
            'validation_groups' => ['Default', 'edit'],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                // Read the binary content of the file
                $blobData = fopen($imageFile->getPathname(), 'rb');
                $oeuvre->setImage($blobData);
            }

            $entityManager->flush();

            if ($request->isXmlHttpRequest()) {
                return $this->json(['success' => true]);
            }

            $this->addFlash('success', 'Œuvre ajoutée avec succès 🎨');

            return $this->redirectToRoute('app_oeuvre_front');
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render('oeuvre/_form_fields.html.twig', [
                'form' => $form->createView(),
            ], new Response('', $form->isSubmitted() ? 422 : 200));
        }

        return $this->render('oeuvre_front/oeuvre_front.html.twig', [
            'form' => $form->createView(),
            'oeuvres' => $oeuvreRepository->findAll(),
        ]);
    }


}
