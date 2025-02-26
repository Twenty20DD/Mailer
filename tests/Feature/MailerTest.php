<?php

use Illuminate\Support\Facades\Http;
use Twenty20\Mailer\Mailer;

beforeEach(function () {
    Http::preventStrayRequests(); // Prevent any outgoing HTTP requests
    // Create a fresh instance of the Mailer class using the package config
    $this->mailer = new Mailer([
        'provider' => 'sendgrid',
        'providers' => [
            'sendgrid' => [
                'api_key' => 'SG.TEST-API-KEY',
                'api_url' => 'https://api.sendgrid.com/v3/mail/send',
            ],
        ],
    ]);
});

test('it sends an email via SendGrid with correct headers', function () {
    Http::fake([
        'https://api.sendgrid.com/v3/mail/send' => Http::response(['message' => 'success'], 202),
    ]);

    $this->mailer->sendMail(
        to: 'recipient@example.com',
        from: 'sender@example.com',
        subject: 'Test Subject',
        body: '<p>Hello World</p>',
        cc: ['cc@example.com'],
        bcc: ['bcc@example.com'],
        replyTo: ['reply@example.com']
    );

    Http::assertSent(function ($request) {
        $data = $request->data();

        // Accessing SendGrid SDK objects using their getter methods
        $personalization = $data['personalizations'][0] ?? null;
        $from = $data['from'] ?? null;
        $replyTo = $data['reply_to'] ?? null;
        $subject = $data['subject'] ?? null;
        $content = $data['content'][1] ?? null;

        return $request->url() === 'https://api.sendgrid.com/v3/mail/send'
            && $request->method() === 'POST'
            && $personalization !== null
            && method_exists($personalization, 'getTos') && $personalization->getTos()[0]->getEmail() === 'recipient@example.com'
            && method_exists($personalization, 'getCcs') && $personalization->getCcs()[0]->getEmail() === 'cc@example.com'
            && method_exists($personalization, 'getBccs') && $personalization->getBccs()[0]->getEmail() === 'bcc@example.com'
            && method_exists($from, 'getEmail') && $from->getEmail() === 'sender@example.com'
            && method_exists($replyTo, 'getEmail') && $replyTo->getEmail() === 'reply@example.com'
            && method_exists($subject, 'getSubject') && $subject->getSubject() === 'Test Subject'
            && method_exists($content, 'getValue') && $content->getValue() === '<p>Hello World</p>';
    });
});


test('it handles a delivered email response', function () {
    Http::fake([
        'https://api.sendgrid.com/v3/mail/send' => Http::response(['message' => 'success'], 202),
    ]);

    $response = $this->mailer->sendMail(
        to: 'delivered@example.com',
        from: 'sender@example.com',
        subject: 'Test Delivered',
        body: '<p>Delivered Email</p>'
    );

    expect($response)->toBeArray()->toHaveKey('message', 'success');
});

test('it handles a bounced email response', function () {
    Http::fake([
        'https://api.sendgrid.com/v3/mail/send' => Http::response([
            'errors' => [['message' => 'The email address bounced.']],
        ], 400),
    ]);

    try {
        $this->mailer->sendMail(
            to: 'bounced@example.com',
            from: 'sender@example.com',
            subject: 'Test Bounce',
            body: '<p>Bounced Email</p>'
        );
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toBe('The email address bounced.');
        return;
    }

    $this->fail('Expected RuntimeException was not thrown.');
});

test('it handles an undelivered email response', function () {
    Http::fake([
        'https://api.sendgrid.com/v3/mail/send' => Http::response([
            'errors' => [['message' => 'Email undelivered due to spam filter.']],
        ], 400),
    ]);

    try {
        $this->mailer->sendMail(
            to: 'spam@example.com',
            from: 'sender@example.com',
            subject: 'Test Undelivered',
            body: '<p>Spam Filtered Email</p>'
        );
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toBe('Email undelivered due to spam filter.');
        return;
    }

    $this->fail('Expected RuntimeException was not thrown.');
});
