<?php
session_start();
require '../includes/conexao.php'; // sua conexão com PDO

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['credential'])) {
        echo "Token não recebido";
        exit();
    }

    $idToken = $data['credential'];
    $client_id = "736385181163-jkc732t8i71u1h606p9qijpusspf7nml.apps.googleusercontent.com";

    // Chama a API do Google para validar o token
    $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $idToken;
    $response = file_get_contents($url);

    if ($response === false) {
        echo "Erro ao validar token no Google";
        exit();
    }

    $payload = json_decode($response, true);

    // Verifica se o token é válido e do seu client_id
    if ($payload && isset($payload['aud']) && $payload['aud'] === $client_id) {
        $email = $payload['email'];
        $nome  = $payload['name'];

        // Verifica se já existe usuário no banco
        $stmt = $pdo->prepare("SELECT * FROM usuario WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            // Usuário já existe → login
            $_SESSION['usuario_id']       = $usuario['idusuario'];
            $_SESSION['usuario_nome']     = $usuario['nome'];
            $_SESSION['usuario_email']    = $usuario['email'];
            $_SESSION['usuario_admin']    = $usuario['admin'];
            $_SESSION['usuario_matricula'] = $usuario['matricula'];
        } else {
            // Cria novo usuário (sem senha, login só pelo Google)
            $stmt = $pdo->prepare("INSERT INTO usuario (nome, email, senha, admin, matricula) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nome, $email, null, 0, null]);

            $_SESSION['usuario_id']       = $pdo->lastInsertId();
            $_SESSION['usuario_nome']     = $nome;
            $_SESSION['usuario_email']    = $email;
            $_SESSION['usuario_admin']    = 0;
            $_SESSION['usuario_matricula'] = null;
        }

        echo "Login Google OK";
    } else {
        echo "Token inválido";
    }
} else {
    echo "Método inválido";
}
