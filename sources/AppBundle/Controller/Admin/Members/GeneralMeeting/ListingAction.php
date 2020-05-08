<?php

namespace AppBundle\Controller\Admin\Members\GeneralMeeting;

use Afup\Site\Utils\PDF_AG;
use AppBundle\GeneralMeeting\GeneralMeetingRepository;
use Assert\Assertion;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Request;

class ListingAction
{
    /** @var GeneralMeetingRepository */
    private $generalMeetingRepository;

    public function __construct(GeneralMeetingRepository $generalMeetingRepository)
    {
        $this->generalMeetingRepository = $generalMeetingRepository;
    }

    public function __invoke(Request $request)
    {
        $latestDate = $this->generalMeetingRepository->getLatestDate();
        Assertion::notNull($latestDate);
        $selectedDate = $latestDate;
        if ($request->query->has('date')) {
            $selectedDate = DateTimeImmutable::createFromFormat('d/m/Y', $request->query->get('date'));
        }
        $attendees = $this->generalMeetingRepository->getAttendees($selectedDate);
        $pdf = new PDF_AG();
        $pdf->setFooterTitle('Assemblée générale '.$selectedDate->format('d/m/Y'));
        $pdf->prepareContent($attendees);
        $pdf->Output('assemblee_generale.pdf', 'D');
        exit;
    }
}
