@component('mail::message')
#  Verificação de E-mail

Olá, {{$user->email}},

Seu código de verificação é:

@component('mail::panel')
{{ $user->verification_code }}
@endcomponent

Digite este código no Kwanga APP para confirmar o seu e-mail.

Obrigado,<br>
{{ config('app.name') }}
@endcomponent
