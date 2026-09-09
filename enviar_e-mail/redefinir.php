<?php
$token = $_GET['token'] ?? '';

//Verifica se o token é válido e não expirou
$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE reset_token = ? AND reset_token_expires_at > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    die("Link inválido ou expirado.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $novaSenha = password_hash($_POST['senha'], PASSWORD_DEFAULT);

    // Atualiza senha e invalida o token usado
    $updateStmt = $pdo->prepare("UPDATE usuarios SET senha = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE id = ?");
    $updateStmt->execute([$novaSenha, $user['id']]);

    echo "Senha alterada com sucesso! Você já pode fazer login.";
}
?>

<!-- Formulário HTML para digitar a nova senha -->
<form method="POST">
    <input type="password" name="senha" placeholder="Digite a nova senha" required>
    <button type="submit">Redefinir Senha</button>
</form>