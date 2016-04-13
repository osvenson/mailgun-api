<?php namespace Mailgun;

function send($data)
{
	// TODO: attachments
	global $mailgun;

	array_map(function ($v) use ($mailgun) {
		if (!isset($mailgun[$v])) throw new Exception('Invalid Mailgun Settings');
	}, array('key', 'domain', 'from_name', 'from_email'));

	if (!isset($data['to'])) 		throw new Exception('Missing <TO>.');
	if (!isset($data['subject'])) 	throw new Exception('Missing <SUBJECT>.');
	if (!isset($data['message'])) 	throw new Exception('Missing <MESSAGE>.');

	$data = array_intersect_key($data, array_flip(array('to', 'subject', 'message', 'cc', 'bcc', 'replyto', 'MessageID')));

	// --

	$data['html'] = $data['message'];
	$data['text'] = strip_tags($data['message']);
	unset($data['message']);

	if (is_array($data['to'])) $data['to'] = implode(',', $data['to']);

	$data['from'] = sprintf('%s <%s>', $mailgun['from_name'], $mailgun['from_email']);

	if (isset($data['replyto']))
	{
		$data['h:Reply-To'] = $data['replyto'];
		unset($data['replyto']);
	}

	if (isset($data['MessageID']))
	{
		$data['h:Message-ID'] = $data['MessageID'][0];
		$data['h:In-Reply-To'] = $data['MessageID'][1];
		unset($data['MessageID']);
	}

	// --

	$url = sprintf('https://api.mailgun.net/v3/%s/messages', $mailgun['domain']);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($ch, CURLOPT_USERPWD, 'api:' . $mailgun['key']);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

	// --

	$response = curl_exec($ch);
	curl_close($ch);
	return $response;
}
