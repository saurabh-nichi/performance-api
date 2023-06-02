<?php

namespace App\Traits;

use Exception;

trait EmailParsing
{
    /**
     * Retrieve emails from email inbox
     * @param string $username - The email address
     * @param string $password - The email account password
     * @param string $subject - The email subject to look for
     * @return array - An array of mails with headers & message body
     */
    public function readEmailInbox(string $username, string $password, string $subject = 'Shopify Japanese Message')
    {
        $username = getenv('IMAP_USERNAME');
        if (in_array('gmail.com', explode('@', $username))) {
            $mailbox = '{imap.gmail.com:993/imap/ssl}INBOX';
        } else {
            $mailbox = '{imap-mail.outlook.com:993/imap/ssl}INBOX';
        }
        $conn = imap_open($mailbox, $username, $password);
        $mailMsgNos = imap_search($conn, 'SUBJECT "' . $subject . '"');
        if (!$mailMsgNos) {
            throw new Exception('No messages in inbox.', 404);
        }
        $mails = [];
        rsort($mailMsgNos);
        foreach ($mailMsgNos as $mailMsgNo) {
            array_push($mails, [
                'headers' => imap_fetch_overview($conn, $mailMsgNo),
                'message_body' => imap_fetchbody($conn, $mailMsgNo, 2)
            ]);
        }
        imap_close($conn);
        return $mails;
    }

    /**
     * Remove/replace html tags having style attributes
     * @param string $body - The string from which tags have to be removed
     * @param string $tag - The html tag that needs removal, excluding "<" & ">" characters
     * @param string $replaceString - The string to replace with
     * @param string $tagStyleSeparator - The separator string between the tag & style attributes
     * @return string|false - Return string with removed/replaced tags, false if any error occurs
     */
    public function removeHtmlTag(string &$body, string $tag, string $replaceString = '\n', string $tagStyleSeparator = ' ')
    {
        if (!str_contains($body, '<' . $tag)) {
            return $body;
        }
        $chars = mb_str_split($body);
        $totalChars = count($chars);
        $tagLength = strlen($tag);
        foreach ($chars as $charIndex => $char) {
            if (!isset($chars[$charIndex + 1])) {
                break;
            }
            if (
                $char == '<' &&
                mb_substr($body, $charIndex + 1, $tagLength) == $tag &&
                (($tagStyleSeparator && $chars[$charIndex + $tagLength + 1] == $tagStyleSeparator) || $tagStyleSeparator == '')
            ) {
                $length = 0;
                for ($i = $charIndex + $tagLength + 1; $i < $totalChars + 1; $i++) {
                    if ($chars[$i] == '>') {
                        $length = $i - $charIndex + 1;
                        break;
                    }
                }
                if ($length > 0) {
                    $substring = mb_substr($body, $charIndex, $length);
                }
                $body = str_replace($substring, $replaceString, $body);
            }
        }
        return $body;
    }
}
