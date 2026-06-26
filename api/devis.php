<?php
/**
 * AUTO pièces service — Formulaire de devis
 * Email propriétaire + confirmation client (HTML)
 */
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); exit('Method Not Allowed');
}

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

/* ── Configuration ── */
$config = [
    'owner_email' => 'infos@auto-pieceservice.fr',
    'owner_name'  => 'AUTO pièces service',
    'from_email'  => 'infos@auto-pieceservice.fr', // doit correspondre à une boîte réelle sur le serveur
    'from_name'   => 'AUTO pièces service',
    'site_name'   => 'AUTO pièces service',
    'address'     => '380 Rue de la Marbrerie, 34740 Vendargues',
    'phone'       => '+33 7 56 81 19 38',
];

/* ── Utilitaires ── */
function sanitize(string $v): string {
    return htmlspecialchars(strip_tags(trim($v)), ENT_QUOTES, 'UTF-8');
}
function jsonOut(bool $ok, string $err = '', int $code = 200): void {
    http_response_code($code);
    echo json_encode(['success' => $ok, 'error' => $err], JSON_UNESCAPED_UNICODE);
    exit;
}
function sendHtml(string $to, string $subject, string $body, array $cfg, string $replyTo = ''): bool {
    $domain  = substr(strrchr($cfg['from_email'], '@'), 1);
    $msgId   = '<' . time() . '.' . bin2hex(random_bytes(8)) . '@' . $domain . '>';
    $h = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . mb_encode_mimeheader($cfg['from_name'], 'UTF-8') . ' <' . $cfg['from_email'] . '>',
        'Return-Path: <' . $cfg['from_email'] . '>',
        'Message-ID: ' . $msgId,
        'Date: ' . date('r'),
        'X-Mailer: PHP/' . phpversion(),
    ];
    if ($replyTo) $h[] = 'Reply-To: ' . $replyTo;
    // Le paramètre -f aligne l'expéditeur SMTP avec From: (requis pour SPF/DKIM Hostinger)
    return mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=',
                $body, implode("\r\n", $h), '-f' . $cfg['from_email']);
}

/* ── Honeypot ── */
if (!empty($_POST['website'])) { jsonOut(true); }

/* ── Inputs ── */
$nom       = sanitize($_POST['nom']       ?? '');
$email     = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$telephone = sanitize($_POST['telephone'] ?? '');
$service   = sanitize($_POST['service']   ?? '') ?: 'Demande rapide';
$vehicule  = sanitize($_POST['vehicule']  ?? '');
$message   = sanitize($_POST['message']   ?? '');
$annee     = filter_input(INPUT_POST, 'annee', FILTER_VALIDATE_INT,
               ['options' => ['min_range' => 1960, 'max_range' => (int)date('Y') + 1]]);

if (!$nom)      jsonOut(false, 'Nom requis.',    422);
if (!$email)    jsonOut(false, 'Email invalide.',422);
if (!$telephone)jsonOut(false, 'Téléphone requis.',422);
if (!$message)  jsonOut(false, 'Message requis.',422);

$date     = date('d/m/Y à H:i');
$vDisplay = $vehicule ? $vehicule . ($annee ? " ($annee)" : '') : 'Non précisé';

/* ════════════════════════════════════════════════════════════════
   EMAIL PROPRIÉTAIRE
════════════════════════════════════════════════════════════════ */
$ownerHtml = <<<HTML
<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;background:#F8FAFC;color:#1E293B}
.w{max-width:600px;margin:0 auto}
.top{background:#BFDB6D;height:4px}
.hd{background:#0F172A;padding:24px 32px}
.hd-title{color:#fff;font-size:18px;font-weight:700;margin-bottom:4px}
.hd-sub{color:#64748B;font-size:13px}
.badge{display:inline-block;background:#BFDB6D;color:#1A2B12;font-size:11px;font-weight:700;padding:3px 10px;border-radius:4px;text-transform:uppercase;letter-spacing:1px;margin-top:10px}
.bd{background:#fff;padding:32px}
.lbl{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:#94A3B8;margin-bottom:16px}
.row{display:flex;gap:16px;padding:12px 0;border-bottom:1px solid #F1F5F9}
.row:last-of-type{border-bottom:none}
.rl{font-size:13px;color:#64748B;min-width:130px;flex-shrink:0;font-weight:500}
.rv{font-size:14px;color:#0F172A;font-weight:600}
.rv a{color:#6B8E2A;text-decoration:none}
.msg{background:#F8FAFC;border-left:4px solid #BFDB6D;padding:16px 20px;border-radius:0 8px 8px 0;margin-top:24px;font-size:14px;line-height:1.7;color:#334155}
.cta{text-align:center;margin-top:28px;padding-top:24px;border-top:1px solid #E2E8F0}
.btn{display:inline-block;padding:12px 28px;background:#BFDB6D;color:#1A2B12;border-radius:6px;text-decoration:none;font-weight:700;font-size:14px}
.ft{background:#F8FAFC;padding:16px 32px;font-size:12px;color:#94A3B8;text-align:center;border-top:1px solid #E2E8F0}
</style></head><body>
<div class="w">
<div class="top"></div>
<div class="hd">
  <div class="hd-title">AUTO pièces service</div>
  <div class="hd-sub">Système de gestion des demandes</div>
  <div class="badge">Nouvelle demande de devis</div>
</div>
<div class="bd">
  <div class="lbl">Informations client</div>
  <div class="row"><span class="rl">Nom</span><span class="rv">{$nom}</span></div>
  <div class="row"><span class="rl">Email</span><span class="rv"><a href="mailto:{$email}">{$email}</a></span></div>
  <div class="row"><span class="rl">Téléphone</span><span class="rv"><a href="tel:{$telephone}">{$telephone}</a></span></div>
  <div class="row"><span class="rl">Service</span><span class="rv">{$service}</span></div>
  <div class="row"><span class="rl">Véhicule</span><span class="rv">{$vDisplay}</span></div>
  <div class="row"><span class="rl">Reçu le</span><span class="rv">{$date}</span></div>
  <div class="lbl" style="margin-top:28px">Message</div>
  <div class="msg">{$message}</div>
  <div class="cta"><a href="mailto:{$email}" class="btn">↩ Répondre au client</a></div>
</div>
<div class="ft">{$config['site_name']} · {$config['address']} · Reçu le {$date}</div>
</div></body></html>
HTML;

/* ════════════════════════════════════════════════════════════════
   EMAIL CONFIRMATION CLIENT
════════════════════════════════════════════════════════════════ */
$clientHtml = <<<HTML
<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;background:#F8FAFC;color:#1E293B}
.w{max-width:600px;margin:0 auto}
.top{background:#BFDB6D;height:4px}
.hd{background:#0F172A;padding:40px 32px;text-align:center}
.circle{width:64px;height:64px;background:#BFDB6D;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px}
.hd h1{color:#fff;font-size:22px;font-weight:800;margin-bottom:8px}
.hd p{color:#64748B;font-size:14px}
.bd{background:#fff;padding:40px 32px}
.greeting{font-size:20px;font-weight:700;color:#0F172A;margin-bottom:14px}
.text{font-size:15px;color:#64748B;line-height:1.7;margin-bottom:12px}
.sum{background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:24px;margin:24px 0}
.sum-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:#94A3B8;margin-bottom:16px}
.sum-row{display:flex;gap:16px;padding:10px 0;border-bottom:1px solid #E2E8F0}
.sum-row:last-child{border-bottom:none}
.sl{font-size:13px;color:#64748B;min-width:100px}
.sv{font-size:13px;color:#0F172A;font-weight:600}
.alert{background:#FFF7F8;border-left:4px solid #BFDB6D;padding:16px 20px;border-radius:0 8px 8px 0;margin:24px 0;font-size:14px;color:#334155;line-height:1.7}
.contacts{display:grid;gap:10px;margin-top:24px}
.ci{display:flex;align-items:center;gap:12px;padding:14px;background:#F8FAFC;border-radius:8px;text-decoration:none;color:inherit}
.ci-icon{width:38px;height:38px;border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.red-bg{background:rgba(191, 219, 109,.25);color:#BFDB6D}
.wa-bg{background:rgba(37,211,102,.1);color:#25D366}
.ci-label{font-size:12px;color:#64748B;margin-bottom:2px}
.ci-value{font-size:14px;font-weight:700;color:#0F172A}
.ft{background:#0F172A;padding:24px 32px;text-align:center}
.ft p{color:#64748B;font-size:13px;line-height:1.7}
.ft a{color:#BFDB6D;text-decoration:none}
</style></head><body>
<div class="w">
<div class="top"></div>
<div class="hd">
  <div class="circle">
    <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#1A2B12" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
  </div>
  <h1>Demande bien reçue !</h1>
  <p>AUTO pièces service — Centre VHU Agréé</p>
</div>
<div class="bd">
  <div class="greeting">Bonjour {$nom},</div>
  <p class="text">Nous avons bien reçu votre demande de devis et nous vous en remercions. Notre équipe va l'étudier dans les meilleurs délais et vous contactera très prochainement.</p>
  <div class="sum">
    <div class="sum-title">Récapitulatif de votre demande</div>
    <div class="sum-row"><span class="sl">Service</span><span class="sv">{$service}</span></div>
    <div class="sum-row"><span class="sl">Véhicule</span><span class="sv">{$vDisplay}</span></div>
    <div class="sum-row"><span class="sl">Téléphone</span><span class="sv">{$telephone}</span></div>
    <div class="sum-row"><span class="sl">Date</span><span class="sv">{$date}</span></div>
  </div>
  <div class="alert">Besoin d'une réponse urgente ? Contactez-nous directement par téléphone ou WhatsApp.</div>
  <div class="contacts">
    <a href="tel:{$config['phone']}" class="ci">
      <div class="ci-icon red-bg">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.64 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.55 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.59a16 16 0 0 0 8.5 8.5l.96-.87a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 24 18.54z"/></svg>
      </div>
      <div><div class="ci-label">Téléphone</div><div class="ci-value">{$config['phone']}</div></div>
    </a>
    <a href="https://wa.me/33756811938" class="ci">
      <div class="ci-icon wa-bg">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M11.997 0C5.373 0 0 5.373 0 11.997c0 2.117.554 4.102 1.523 5.83L.057 23.943l6.266-1.445a11.94 11.94 0 0 0 5.674 1.443c6.624 0 11.997-5.373 11.997-11.997C23.994 5.373 18.621 0 11.997 0zm0 21.94a9.938 9.938 0 0 1-5.063-1.377l-.363-.215-3.722.858.891-3.621-.237-.372A9.929 9.929 0 0 1 2.06 12c0-5.483 4.457-9.94 9.94-9.94 5.482 0 9.94 4.457 9.94 9.94 0 5.482-4.458 9.94-9.943 9.94z"/></svg>
      </div>
      <div><div class="ci-label">WhatsApp</div><div class="ci-value">+33 7 56 81 19 38</div></div>
    </a>
  </div>
</div>
<div class="ft">
  <p><strong style="color:#fff">AUTO pièces service</strong> — Centre VHU Agréé<br>
  {$config['address']}<br>
  <a href="mailto:{$config['owner_email']}">{$config['owner_email']}</a><br><br>
  Cet email a été envoyé automatiquement. Merci de ne pas y répondre directement.</p>
</div>
</div></body></html>
HTML;

/* ── Envoi ── */
$ownerSent  = sendHtml($config['owner_email'],
    "Nouvelle demande de devis — {$nom}",
    $ownerHtml, $config, "{$nom} <{$email}>");

$clientSent = sendHtml($email,
    "Confirmation de votre demande — AUTO pièces service",
    $clientHtml, $config);

if (!$ownerSent) {
    error_log("[AUTO pièces service] Échec email propriétaire pour {$nom} <{$email}> le {$date}");
}
if (!$clientSent) {
    error_log("[AUTO pièces service] Échec email confirmation client {$email} le {$date}");
}

// On retourne toujours succès si les données sont valides
// L'échec mail() est loggé côté serveur mais ne bloque pas le client
jsonOut(true);
