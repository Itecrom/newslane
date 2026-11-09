A new {{ $contact->request_type }} request was submitted to NEWSLANE.

Name: {{ $contact->name }}
Email: {{ $contact->email }}
Organization: {{ $contact->organization ?: 'Not provided' }}
Subject: {{ $contact->subject }}

Sign in to the NEWSLANE administrator dashboard to review the request.
