<x-mail::message>
# {{ __('Team Invitation') }}

{{ __('You have been invited to join the **:team** team.', ['team' => $team->name]) }}

{{ __('If you did not expect to receive an invitation to this team, you may discard this email.') }}

<x-mail::button :url="$acceptUrl">
{{ __('Accept Invitation') }}
</x-mail::button>

{{ __('If you’re having trouble clicking the button, copy and paste the URL below into your web browser:') }}

[{{ $acceptUrl }}]({{ $acceptUrl }})

{{ __('Thanks') }},<br>
{{ config('app.name') }}
</x-mail::message>
