Hello {{ $user->name }},

An administrator created a Kulaza Pet Supplies staff account for you.

Username: {{ $user->username }}

Set your password using this one-time link:
{{ $setupUrl }}

This link expires in 60 minutes. If you did not expect this email, you can ignore it.
