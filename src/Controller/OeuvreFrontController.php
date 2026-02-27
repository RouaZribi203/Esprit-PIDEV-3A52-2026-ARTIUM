<?php

namespace App\Controller;

use App\Entity\Oeuvre;
use App\Entity\User;
use App\Enum\Specialite;
use App\Enum\TypeOeuvre;
use App\Form\OeuvreType;
use App\Form\UserType;
use App\Service\EmbeddingService;
use App\Service\ImageEmbeddingService;
use App\Repository\OeuvreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Message\GenerateEmbeddingMessage;
use Symfony\Component\Messenger\MessageBusInterface;


final class OeuvreFrontController extends AbstractController
{
    #[Route('/artiste/profil/offcanvas/{offcanvasId}', name: 'app_artiste_profile_offcanvas', methods: ['GET'])]
    public function profileOffcanvas(string $offcanvasId = 'offcanvasProfile'): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $profileForm = $this->createForm(UserType::class, $user, ['is_edit' => true]);

        return $this->render('Front Office/Partials/offcanvas_profile.html.twig', [
            'user' => $user,
            'form' => $profileForm->createView(),
            'offcanvas_id' => $offcanvasId,
            'title' => 'Modifier profil',
            'subtitle' => 'Mettez à jour vos informations personnelles',
            'profile_form_action' => $this->generateUrl('app_artiste_profile_update'),
            'password_form_action' => '#',
            'show_password_form' => true,
        ]);
    }

    #[Route('/artiste/profil/modifier', name: 'app_artiste_profile_update', methods: ['POST'])]
    public function updateProfile(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_signin');
        }

        $profileForm = $this->createForm(UserType::class, $user, ['is_edit' => true]);
        $profileForm->handleRequest($request);

        if ($profileForm->isSubmitted() && $profileForm->isValid()) {
            $photoFile = $profileForm->get('photoProfil')->getData();
            if ($photoFile instanceof UploadedFile) {
                $newFilename = uniqid('user_').'.'.$photoFile->guessExtension();
                $photoFile->move($this->getParameter('kernel.project_dir').'/public/uploads', $newFilename);
                $user->setPhotoProfil($newFilename);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Profil mis à jour !');
        } else {
            $this->addFlash('error', 'Veuillez corriger les erreurs du formulaire profil.');
        }

        $referer = $request->headers->get('referer');

        return $this->redirect($referer ?: $this->generateUrl('app_oeuvre_front'));
    }

    #[Route('/mes_oeuvres', name: 'app_oeuvre_front')]
    public function index(): Response
    {
        $user = $this->getUser();
        $oeuvre = new Oeuvre();
        $oeuvres = $this->getCurrentUserOeuvres();
        $form = $this->createForm(OeuvreType::class, $oeuvre, [
            'include_date' => false,
            'user' => $user,
            'include_type' => false,
        ]);

        return $this->render('oeuvre_front/oeuvre_front.html.twig', [
            'controller_name' => 'OeuvreFrontController',
            'form' => $form->createView(),
            'oeuvres' => $oeuvres,
            'typeOeuvres' => TypeOeuvre::cases(),
        ]);
    }
    #[Route('/new_oeuvre', name: 'app_oeuvre_new', methods: ['GET','POST'])]
    public function new(Request $request,EntityManagerInterface $entityManager,EmbeddingService $embeddingService,ImageEmbeddingService $imageEmbeddingService,MessageBusInterface $bus): Response {
        
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

    /*// --- HERE: wrap moved file and send to embedding service ---
    $uploadedFile = new \Symfony\Component\HttpFoundation\File\UploadedFile(
        $tempDir . '/' . $newName,
        $newName,
        null,
        null,
        true // mark as "test" so Symfony doesn’t try to move it again
    );

    // send blob to FastAPI via your service
    $imageEmbedding = $imageEmbeddingService->getEmbeddingFromBlob($uploadedFile);

    // save embedding in your Oeuvre entity
    $oeuvre->setEmbedding($imageEmbedding);

    // --- END embedding call ---*/

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
        try {
            $bus->dispatch(new GenerateEmbeddingMessage($oeuvre->getId()));
        } catch (\Throwable) {
        }

        return $this->redirectToRoute('app_oeuvre_front');
       }

       $oeuvres = $this->getCurrentUserOeuvres();

       return $this->render('oeuvre_front/oeuvre_front.html.twig', [
           'form' => $form->createView(),
           'oeuvres' => $oeuvres,
           'typeOeuvres' => TypeOeuvre::cases(),
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
    public function edit(Request $request, Oeuvre $oeuvre, EntityManagerInterface $entityManager): Response {
        
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
            'oeuvres' => $this->getCurrentUserOeuvres(),
            'typeOeuvres' => TypeOeuvre::cases(),
        ]);
    }

    #[Route('/mes_oeuvres/{id}/image', name: 'app_oeuvre_front_image', methods: ['GET'])]
    public function oeuvreImage(Oeuvre $oeuvre): Response
    {
        $imageData = $oeuvre->getImage();

        if (!$imageData) {
            throw $this->createNotFoundException('Image not found');
        }

        if (is_resource($imageData)) {
            rewind($imageData);
            $imageData = stream_get_contents($imageData);
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($imageData) ?: 'image/jpeg';

        return new Response(
            $imageData,
            Response::HTTP_OK,
            ['Content-Type' => $mimeType]
        );
    }

    private function getCurrentUserOeuvres(): array
    {
        $user = $this->getUser();
        $oeuvres = [];

        if (!$user || !method_exists($user, 'getCollections')) {
            return $oeuvres;
        }

        foreach ($user->getCollections() as $collection) {
            if (!method_exists($collection, 'getOeuvres')) {
                continue;
            }

            foreach ($collection->getOeuvres() as $oeuvreItem) {
                $oeuvres[] = $oeuvreItem;
            }
        }

        return $oeuvres;
    }


}
