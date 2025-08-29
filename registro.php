<?php
session_start();
if (isset($_SESSION['usuario_id'])) {
    header('Location: hub.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TydraPI - Registro</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

  <!-- Navbar -->
  <header class="header">
    <div class="header-container">
      <a href="/" class="logo">TydraPI</a>
      <nav class="header-nav">
        <a href="/" class="nav-link">Início</a>
        <a href="/sobre" class="nav-link">Sobre</a>
        <a href="/recursos" class="nav-link">Recursos</a>
        <button class="btn-entrar">Entrar</button>
      </nav>
    </div>
  </header>

  <!-- Conteúdo Principal -->
  <main class="main-container">
    <div class="login-card">
      <div class="login-header">
        <h1 class="login-title">Crie sua conta</h1>
        <p class="login-subtitle">Junte-se à nossa comunidade de inovadores</p>
      </div>

      <form action="functions/registerProcess.php" method="post">
        <div class="form-group">
          <label for="fullname" class="form-label">Nome completo</label>
          <div class="input-wrapper">
            <span class="input-icon"><i class="fas fa-user"></i></span>
            <input type="text" id="fullname" name="fullname" placeholder="Seu nome"
                   class="form-input" required>
          </div>
        </div>

        <div class="form-group">
          <label for="email" class="form-label">Email</label>
          <div class="input-wrapper">
            <span class="input-icon"><i class="fas fa-envelope"></i></span>
            <input type="email" id="email" name="email" placeholder="seu@email.com"
                   class="form-input" required>
          </div>
        </div>

        <div class="form-group">
          <label for="password" class="form-label">Senha</label>
          <div class="input-wrapper">
            <span class="input-icon"><i class="fas fa-lock"></i></span>
            <input type="password" id="password" name="password" placeholder="••••••••"
                   class="form-input password-input" required>
            <button type="button" class="toggle-password"><i class="fas fa-eye"></i></button>
          </div>
        </div>

        <div class="form-group">
          <label for="confirm-password" class="form-label">Confirmar senha</label>
          <div class="input-wrapper">
            <span class="input-icon"><i class="fas fa-lock"></i></span>
            <input type="password" id="confirm-password" name="confirm-password" placeholder="••••••••"
                   class="form-input password-input" required>
            <button type="button" class="toggle-password"><i class="fas fa-eye"></i></button>
          </div>
        </div>

        <div class="form-options">
          <div class="remember-me">
            <div class="checkbox-wrapper">
              <input type="checkbox" name="terms" id="terms" class="remember-checkbox" required>
            </div>
            <label for="terms" class="remember-text">Eu aceito os termos de uso e política de privacidade</label>
          </div>
        </div>

        <button type="submit" class="btn-submit">
          Criar conta
        </button>
      </form>

      <div class="register-text">
        Já tem uma conta? <a href="index.php" class="register-link">Entre agora</a>
      </div>
    </div>
  </main>

  <!-- Footer -->
  <footer class="footer">
    <div class="footer-container">
      <div class="footer-section footer-about">
        <h3>TydraPI</h3>
        <p>Rede social escolar de conexões instantâneas que prioriza conversas autênticas e interações significativas.</p>
        <div class="social-icons">
          <a href="#" class="social-link"><i class="fab fa-github"></i></a>
          <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
          <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
          <a href="#" class="social-link"><i class="fas fa-envelope"></i></a>
        </div>
      </div>
      
      <div class="footer-section footer-nav">
        <h3>Navegação</h3>
        <ul class="footer-links">
          <li class="footer-link"><a href="/">Início</a></li>
          <li class="footer-link"><a href="/sobre">Sobre</a></li>
          <li class="footer-link"><a href="/recursos">Recursos</a></li>
          <li class="footer-link"><a href="/contato">Contato</a></li>
        </ul>
      </div>
      
      <div class="footer-section footer-legal">
        <h3>Legal</h3>
        <ul class="footer-links">
          <li class="footer-link"><a href="/terms">Termos de Uso</a></li>
          <li class="footer-link"><a href="/privacy">Política de Privacidade</a></li>
          <li class="footer-link"><a href="/cookies">Cookies</a></li>
        </ul>
        
        <div class="contact-info">
          <h3>Contato</h3>
          <p>Email: <a href="mailto:contato@tydrapi.com">contato@tydrapi.com</a></p>
          <p>Suporte: <a href="mailto:suporte@tydrapi.com">suporte@tydrapi.com</a></p>
        </div>
      </div>
    </div>
    
    <div class="copyright">
      &copy; 2025 TydraPI. Todos os direitos reservados.
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Script para toggle de password
    document.querySelectorAll('.toggle-password').forEach(button => {
      button.addEventListener('click', function() {
        const input = this.parentElement.querySelector('.password-input');
        const icon = this.querySelector('i');
        
        if (input.type === 'password') {
          input.type = 'text';
          icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
          input.type = 'password';
          icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
      });
    });
  </script>
</body>
<?php if (!empty($_SESSION['erros'])): ?>
    <div class="alert alert-danger">
        <ul>
            <?php foreach ($_SESSION['erros'] as $erro): ?>
                <li><?= $erro ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php unset($_SESSION['erros']); ?>
<?php endif; ?>
</html>