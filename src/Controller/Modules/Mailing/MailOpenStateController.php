<?php

namespace App\Controller\Modules\Mailing;

use App\Entity\Modules\Mailing\Mail;
use App\Entity\Modules\Mailing\MailOpenState;
use App\Repository\Modules\Mailing\MailOpenStateRepository;
use DateTime;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Handles logic of the {@see MailOpenState}
 */
class MailOpenStateController extends AbstractController
{
    public function __construct(
        private MailOpenStateRepository $mailOpenStateRepository,
        private UrlGeneratorInterface   $urlGenerator
    ){}

    /**
     * For more information check {@see MailOpenStateRepository::isOpened()}
     *
     * @param int    $emailId
     *
     * @return bool|null
     * @throws NonUniqueResultException
     */
    public function isOpened(int $emailId): ?bool
    {
        return $this->mailOpenStateRepository->isOpened($emailId);
    }

    /**
     * Generates token used for opening the E-Mail
     * Uses simple logic to generate as it doesn't need to be anything super secure,
     * just something fine enough so that nobody will mess around the url in the E-Mail trying to
     * set state to other E-Mails
     *
     * @return string
     */
    public function generateOpeningToken(): string
    {
        $randomNumber      = rand(1, 99999);
        $otherRandomNumber = $randomNumber + (int)(new DateTime())->format("YmdHIs");
        $currentDateStamp  = (new DateTime())->modify("+{$randomNumber} days")->getTimestamp();
        $randomId          = uniqid();
        $baseString        = $randomNumber . $otherRandomNumber . $currentDateStamp . $randomId;
        $token             = md5($baseString);

        return $token;
    }

    /**
     * For more information check {@see MailOpenStateRepository::setOpenedState()}
     *
     * @param string $token
     */
    public function setOpenedState(string $token): void
    {
        $this->mailOpenStateRepository->setOpenedState($token);
    }

    /**
     * Will add open state tracker
     *
     * @param Mail $mail
     *
     * @return Mail
     */
    public function addOpenStateEntity(Mail $mail): Mail {
        $openingToken  = $this->generateOpeningToken();
        $mailOpenState = new MailOpenState($openingToken);

        $mail->setOpenState($mailOpenState);

        return $mail;
    }

    /**
     * Will generate url used for marking the E-Mail as open
     *
     * @param MailOpenState $mailOpenState
     *
     * @return string
     */
    public function generateOpenConfirmationLink(MailOpenState $mailOpenState): string
    {
        $url = $this->urlGenerator->generate(
            "api_external_mailing_set_open_state",
            ["token" => $mailOpenState->getOpeningToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return $url;
    }

}