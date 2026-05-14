<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Methode non autorisee']);
    exit();
}

$AIRTABLE_TOKEN = 'patR7IrP3C85SNVCD.789c4510ec4a401a5ffca80e2ad85c550a28b78f5dfc80a0838c0dac64b322b7';
$AIRTABLE_BASE  = 'app53swuwJTeSaJdj';
$AIRTABLE_TABLE = 'Leads';

$body = file_get_contents('php://input');
$data = json_decode($body, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Donnees invalides']);
    exit();
}

// ==================== CREATE ou UPDATE ====================
if (isset($data['id']) && !empty($data['id'])) {
    // MODE UPDATE (après paiement PayPal)
    $recordId = $data['id'];
    $url = "https://api.airtable.com/v0/{$AIRTABLE_BASE}/" . urlencode($AIRTABLE_TABLE) . "/{$recordId}";

    $fields = [];
    if (isset($data['Statut'])) $fields['Statut'] = $data['Statut'];
    if (isset($data['TransactionID'])) $fields['TransactionID'] = $data['TransactionID'];
    if (isset($data['Montant'])) $fields['Montant'] = $data['Montant'];

    $payload = json_encode(['fields' => $fields]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => "PATCH",
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            "Authorization: Bearer {$AIRTABLE_TOKEN}",
            "Content-Type: application/json",
        ],
    ]);
} else {
    // MODE CREATE
    $payload = json_encode([
        'records' => [[
            'fields' => [
                'Prenom'            => $data['Prenom'] ?? '',
                'Nom'               => $data['Nom'] ?? '',
                'Email'             => $data['Email'] ?? '',
                'Téléphone'         => $data['Telephone'] ?? '',
                'Pays'              => $data['Pays'] ?? '',
                'Livre téléchargé'  => $data['Livre'] ?? '',
                'Newsletter'        => (bool)($data['Newsletter'] ?? false),
                'Date inscription'  => date('Y-m-d'),
                'Statut'            => $data['Statut'] ?? 'Nouveau',
                'Montant'           => $data['Montant'] ?? null,
            ]
        ]]
    ]);

    $url = "https://api.airtable.com/v0/{$AIRTABLE_BASE}/" . urlencode($AIRTABLE_TABLE);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            "Authorization: Bearer {$AIRTABLE_TOKEN}",
            "Content-Type: application/json",
        ],
    ]);
}

$response  = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur reseau : ' . $curlError]);
    exit();
}

http_response_code($httpCode);
echo $response;