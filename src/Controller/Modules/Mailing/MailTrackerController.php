<?php

namespace App\Controller\Modules\Mailing;

use App\DTO\Modules\Mailing\MailDTO;
use App\Entity\Modules\Mailing\Mail;

/**
 * Handles providing variety of tracking in E-Mail by:
 * - creating tracking based entities & adding them to {@see Mail},
 * - adding tracking urls / snippets inside the E-Mail by replacing the E-Mail content,
 */
class MailTrackerController
{
    public function __construct(
        private MailOpenStateController $mailOpenStateController,
    ) {}

    public function handle(Mail $mail, MailDTO $mailDto): Mail
    {
        $this->handleOpenState($mail, $mailDto);
        return $mail;
    }

    /**
     * Will handle adding the open state entities, and setting the trackers in E-Mail body
     *
     * @param Mail    $mail
     * @param MailDTO $mailDto
     *
     * @return Mail
     */
    private function handleOpenState(Mail $mail, MailDTO $mailDto): Mail
    {
        if ($mailDto->isTrackOpenState()) {
            $mail = $this->mailOpenStateController->addOpenStateEntity($mail);
            $url              = $this->mailOpenStateController->generateOpenConfirmationLink($mail->getOpenState());
            $replacedMailBody = $this->replaceMailBody($mail->getBody(), $url);
            $mail->setBody($replacedMailBody);
        }

        return $mail;
    }

    /**
     * Will replace the mail body so that the tracker content
     *
     * @param string $mailBody
     * @param string $trackerLink
     *
     * @return string
     */
    private function replaceMailBody(string $mailBody, string $trackerLink): string
    {
        $trackerTag = "<img src='{$trackerLink}' style='display:none;'/>";
        if (str_contains($mailBody, "</body>")) {
            $mailBody = str_replace("</body>", $trackerTag . "</body>", $mailBody);
            return $mailBody;
        }

        $mailBody .= $trackerTag;
        return $mailBody;
    }

}