@php($en = $lang === 'en')
<!DOCTYPE html>
<html lang="{{ $lang }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>{{ $en ? 'Unsubscribed' : 'Désinscription confirmée' }}</title>
<style>
  body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; background: #F3F5F9; font-family: Arial, Helvetica, sans-serif; color: #0B1530; box-sizing: border-box; }
  main { max-width: 480px; background: #FFFFFF; border: 1px solid #E1E6F0; border-radius: 12px; padding: 36px 32px; text-align: center; }
  h1 { margin: 0 0 12px; font-size: 24px; }
  p { margin: 0 0 24px; font-size: 16px; line-height: 1.6; color: #55607A; }
  a { display: inline-block; padding: 12px 20px; border-radius: 8px; background: #2448C8; color: #FFFFFF; text-decoration: none; font-weight: bold; }
</style>
</head>
<body>
<main>
  <h1>{{ $en ? 'You are unsubscribed' : 'Vous êtes désinscrit(e)' }}</h1>
  <p>{{ $en ? 'You will no longer receive the newsletter. You can subscribe again at any time from the website.' : 'Vous ne recevrez plus la newsletter. Vous pouvez vous réinscrire à tout moment depuis le site.' }}</p>
  <a href="{{ $site }}">{{ $en ? 'Back to the website' : 'Retour au site' }}</a>
</main>
</body>
</html>
