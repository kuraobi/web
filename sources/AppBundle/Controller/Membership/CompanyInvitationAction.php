<?php

namespace AppBundle\Controller\Membership;

use AppBundle\Association\Event\NewMemberEvent;
use AppBundle\Association\Form\UserType;
use AppBundle\Association\Model\CompanyMemberInvitation;
use AppBundle\Association\Model\Repository\CompanyMemberInvitationRepository;
use AppBundle\Association\Model\Repository\CompanyMemberRepository;
use AppBundle\Association\Model\Repository\UserRepository;
use AppBundle\Association\Model\User;
use AppBundle\Controller\BlocksHandler;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class CompanyInvitationAction
{
    /** @var CompanyMemberInvitationRepository */
    private $companyMemberInvitationRepository;
    /** @var UserRepository */
    private $userRepository;
    /** @var CompanyMemberRepository */
    private $companyMemberRepository;
    /** @var BlocksHandler */
    private $blocksHandler;
    /** @var FormFactoryInterface */
    private $formFactory;
    /** @var FlashBagInterface */
    private $flashBag;
    /** @var EventDispatcherInterface */
    private $eventDispatcher;
    /** @var UrlGeneratorInterface */
    private $urlGenerator;
    /** @var Environment */
    private $twig;

    public function __construct(
        CompanyMemberInvitationRepository $companyMemberInvitationRepository,
        UserRepository $userRepository,
        CompanyMemberRepository $companyMemberRepository,
        BlocksHandler $blocksHandler,
        FormFactoryInterface $formFactory,
        FlashBagInterface $flashBag,
        EventDispatcherInterface $eventDispatcher,
        UrlGeneratorInterface $urlGenerator,
        Environment $twig
    ) {
        $this->companyMemberInvitationRepository = $companyMemberInvitationRepository;
        $this->userRepository = $userRepository;
        $this->companyMemberRepository = $companyMemberRepository;
        $this->blocksHandler = $blocksHandler;
        $this->formFactory = $formFactory;
        $this->flashBag = $flashBag;
        $this->eventDispatcher = $eventDispatcher;
        $this->urlGenerator = $urlGenerator;
        $this->twig = $twig;
    }

    public function __invoke(Request $request)
    {
        $token = $request->attributes->get('token');
        $invitation = $this->companyMemberInvitationRepository->getOneBy([
            'id' => $request->attributes->get('invitationId'),
            'token' => $token,
            'status' => CompanyMemberInvitation::STATUS_PENDING
        ]);
        $company = null;
        if ($invitation) {
            $company = $this->companyMemberRepository->get($invitation->getCompanyId());
        }
        if ($invitation === null || $company === null) {
            throw new NotFoundHttpException(sprintf('Could not find invitation with token "%s"', $token));
        }
        $user = $this->userRepository->loadUserByEmaiOrAlternateEmail($invitation->getEmail());
        if (null !== $user) {
            return new RedirectResponse($this->urlGenerator->generate('company_invitation_accept', [
                'invitationId' => $invitation->getId(),
                'token' => $token
            ]));
        }

        $userForm = $this->formFactory->create(UserType::class);
        $userForm->handleRequest($request);
        if ($userForm->isSubmitted() && $userForm->isValid()) {
            /** @var User $user */
            $user = $userForm->getData();
            $user->setStatus(User::STATUS_ACTIVE);
            $user->setCompanyId($company->getId());
            if ($invitation->getManager()) {
                $user->setRoles(['ROLE_COMPANY_MANAGER', 'ROLE_USER']);
            }
            $invitation->setStatus(CompanyMemberInvitation::STATUS_ACCEPTED);
            $this->userRepository->save($user);
            $this->companyMemberInvitationRepository->save($invitation);
            $this->flashBag->add('success', 'Votre compte a été créé !');
            $this->eventDispatcher->dispatch(NewMemberEvent::NAME, new NewMemberEvent($user));

            return new RedirectResponse($this->urlGenerator->generate('member_index'));
        }

        return new Response($this->twig->render('site/company_membership/member_invitation.html.twig', [
            'company' => $company,
            'form' => $userForm->createView()
        ] + $this->blocksHandler->getDefaultBlocks()));
    }
}
