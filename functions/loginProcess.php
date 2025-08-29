<?php 
session_start();
require '../includes/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $senha = $_POST['password'];

    // Buscar usuário pelo email
    $stmt = $pdo->prepare("SELECT * FROM usuario WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        // Autenticação bem-sucedida
        $_SESSION['usuario_id'] = $usuario['idusuario'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_email'] = $usuario['email'];
        $_SESSION['usuario_admin'] = $usuario['admin'];
        $_SESSION['usuario_matricula'] = $usuario['matricula'];
        
        // Redirecionar para hub
        header('Location: ../hub.php');
        exit();
    } else {
        $_SESSION['erro'] = "Email ou senha incorretos";
        header('Location: ../index.php');
        exit();
    }
} else {
    header('Location: ../index.php');
    exit();
}
?>