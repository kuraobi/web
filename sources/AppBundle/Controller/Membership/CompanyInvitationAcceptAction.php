<?php

namespace AppBundle\Controller\Membership;

use AppBundle\Association\Event\NewMemberEvent;
use AppBundle\Association\Model\CompanyMemberInvitation;
use AppBundle\Association\Model\Repository\CompanyMemberInvitationRepository;
use AppBundle\Association\Model\Repository\CompanyMemberRepository;
use AppBundle\Association\Model\Repository\UserRepository;
use AppBundle\Association\Model\User;
use AppBundle\Controller\BlocksHandler;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;
use Twig\Environment;

class CompanyInvitationAcceptAction
{
    /** @var CompanyMemberInvitationRepository */
    private $companyMemberInvitationRepository;
    /** @var UserRepository */
    private $userRepository;
    /** @var CompanyMemberRepository */
    private $companyMemberRepository;
    /** @var BlocksHandler */
    private $blocksHandler;
    /** @var FlashBagInterface */
    private $flashBag;
    /** @var EventDispatcherInterface */
    private $eventDispatcher;
    /** @var UrlGeneratorInterface */
    private $urlGenerator;
    /** @var Security */
    private $security;
    /** @var Environment */
    private $twig;

    public function __construct(
        CompanyMemberInvitationRepository $companyMemberInvitationRepository,
        UserRepository $userRepository,
        CompanyMemberRepository $companyMemberRepository,
        BlocksHandler $blocksHandler,
        FlashBagInterface $flashBag,
        EventDispatcherInterface $eventDispatcher,
        UrlGeneratorInterface $urlGenerator,
        Security $security,
        Environment $twig
    ) {
        $this->companyMemberInvitationRepository = $companyMemberInvitationRepository;
        $this->userRepository = $userRepository;
        $this->companyMemberRepository = $companyMemberRepository;
        $this->blocksHandler = $blocksHandler;
        $this->flashBag = $flashBag;
        $this->eventDispatcher = $eventDispatcher;
        $this->urlGenerator = $urlGenerator;
        $this->security = $security;
        $this->twig = $twig;
    }

    public function __invoke(Request $request)
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $token = $request->attributes->get('token');
        $invitation = $this->companyMemberInvitationRepository->getOneBy([
            'id' => $request->attributes->get('invitationId'),
            'token' => $token,
            'status' => CompanyMemberInvitation::STATUS_PENDING,
        ]);
        $company = null;
        if ($invitation) {
            $company = $this->companyMemberRepository->get($invitation->getCompanyId());
        }
        if ($invitation === null || $company === null) {
            throw new NotFoundHttpException(sprintf('Could not find invitation with token "%s"', $token));
        }
        if (!in_array($invitation->getEmail(), [$user->getEmail(), $user->getAlternateEmail()], true)) {
            $this->flashBag->add('error', 'Cette invitation ne vous est pas destinée.');

            return new RedirectResponse($this->urlGenerator->generate('member_index'));
        }
        if ($request->query->get('accept')) {
            $user->setStatus(User::STATUS_ACTIVE);
            $user->setCompanyId($company->getId());
            if ($invitation->getManager()) {
                $user->addRole('ROLE_COMPANY_MANAGER');
            }
            $invitation->setStatus(CompanyMemberInvitation::STATUS_ACCEPTED);
            $this->userRepository->save($user);
            $this->companyMemberInvitationRepository->save($invitation);
            $this->flashBag->add('success', 'Votre compte a été associé à la société !');
            $this->eventDispatcher->dispatch(NewMemberEvent::NAME, new NewMemberEvent($user));

            return new RedirectResponse($this->urlGenerator->generate('member_index'));
        }

        return new Response($this->twig->render('site/company_membership/member_invitation_accept.html.twig', [
            'company' => $company,
            'token' => $token,
            'id' => $invitation->getId(),
        ] + $this->blocksHandler->getDefaultBlocks()));
    }
}
