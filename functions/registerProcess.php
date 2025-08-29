<?php
require '../includes/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $senha = $_POST['password'];
    $confirmarSenha = $_POST['confirm-password'];

    // Validações
    $erros = [];
    
    if (empty($nome) || strlen($nome) < 3) {
        $erros[] = "Nome inválido (mínimo 3 caracteres)";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = "Email inválido";
    }
    
    if (strlen($senha) < 8) {
        $erros[] = "Senha deve ter no mínimo 8 caracteres";
    }
    
    if ($senha !== $confirmarSenha) {
        $erros[] = "As senhas não coincidem";
    }
    
    // Verificar se email já existe
    $stmt = $pdo->prepare("SELECT * FROM usuario WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        $erros[] = "Este email já está cadastrado";
    }

    // Se não houver erros, cadastrar usuário
    if (empty($erros)) {
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        $matricula = 'U' . date('Y') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $stmt = $pdo->prepare("INSERT INTO usuario 
            (matricula, idade, email, nome, senha, data_criacao, status, admin) 
            VALUES (?, 0, ?, ?, ?, NOW(), 'ativo', 0)");
        
        try {
            $stmt->execute([$matricula, $email, $nome, $senhaHash]);
            $_SESSION['sucesso'] = "Cadastro realizado com sucesso! Faça login.";
            header('Location: ../index.php');
            exit();
        } catch (PDOException $e) {
            $erros[] = "Erro no cadastro: " . $e->getMessage();
        }
    }
    
    if (!empty($erros)) {
        $_SESSION['erros'] = $erros;
        header('Location: ../registro.php');
        exit();
    }
} else {
    header('Location: ../registro.php');
    exit();
}
?>