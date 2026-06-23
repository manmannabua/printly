<x-mail::message>
# New Contact Form Message

You have received a new message from the {{ $companyName }} careers website.

**From:** {{ $senderName }} ({{ $senderEmail }})

**Subject:** {{ $messageSubject }}

---

{{ $messageBody }}

---

You can reply directly to this email to respond to the sender.

Thanks,<br>
{{ $companyName }}
</x-mail::message>
