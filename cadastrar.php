<?php
session_start();
require_once 'conexao.php';

function validaCPF($cpf) {
    $c = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($c) != 11 || preg_match('/(\d)\1{10}/', $c)) return false;
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $p = 0; $p < $t; $p++) {
            $d += $c[$p] * (($t + 1) - $p);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($c[$p] != $d) return false;
    }
    return true;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST['nome']);
    $cpf = trim($_POST['cpf']);
    $matricula = trim($_POST['matricula']);
    $email = trim($_POST['email']);
    $data_nascimento = $_POST['data_nascimento'];
    $senha = $_POST['senha'];
    $senha_conf = $_POST['senha_conf'];
    
    $erros = [];
    
    if (empty($nome) || strlen($nome) < 3) $erros['cad_nome'] = "O nome deve ter pelo menos 3 letras.";
    if (!validaCPF($cpf)) $erros['cad_cpf'] = "CPF inválido.";
    if (empty($matricula) || strlen($matricula) !== 10 || !is_numeric($matricula)) $erros['cad_matricula'] = "Digite 10 números.";
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros['cad_email'] = "Digite um e-mail válido.";
    } elseif (!str_ends_with($email, '@aluno.iffar.edu.br')) {
        $erros['cad_email'] = "O e-mail deve ser do domínio @aluno.iffar.edu.br.";
    }

    $data_atual = date("Y-m-d");
    if (empty($data_nascimento) || $data_nascimento > $data_atual) $erros['cad_nascimento'] = "Data inválida.";

    if (strlen($senha) < 8 || !preg_match('/[A-Z]/', $senha) || !preg_match('/[0-9]/', $senha) || !preg_match('/[^A-Za-z0-9]/', $senha)) {
        $erros['cad_senha'] = "A senha não atende aos requisitos mínimos de segurança.";
    } elseif ($senha !== $senha_conf) {
        $erros['cad_senha'] = "As senhas não coincidem.";
    }

    $cpf_limpo = preg_replace('/[^0-9]/', '', $cpf);

    $stmt_check = $conn->prepare("SELECT id_user, Email, CPF, Matricula FROM Usuarios WHERE CPF = ? OR Matricula = ? OR Email = ?");
    $stmt_check->bind_param("sss", $cpf_limpo, $matricula, $email);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows > 0) {
        while ($row = $result_check->fetch_assoc()) {
            if ($row['Email'] === $email) {
                $erros['cad_email'] = "Este e-mail já está cadastrado no sistema.";
            }
            if ($row['CPF'] === $cpf_limpo) {
                $erros['cad_cpf'] = "Este CPF já está cadastrado.";
            }
            if ($row['Matricula'] === $matricula) {
                $erros['cad_matricula'] = "Esta matrícula já está cadastrada.";
            }
        }
    }
    $stmt_check->close();

    if (count($erros) > 0) {
        $_SESSION['erros_cadastro'] = $erros;
        header("Location: index.php"); 
        exit();
    }

    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    $validado = 'nao';
    
    $stmt_insert = $conn->prepare("INSERT INTO Usuarios (Nome, CPF, Matricula, Email, Data_nasc, Senha, Tipo_user, validado) VALUES (?, ?, ?, ?, ?, ?, 'padrao', ?)");
    $stmt_insert->bind_param("sssssss", $nome, $cpf_limpo, $matricula, $email, $data_nascimento, $senha_hash, $validado);
    
    if ($stmt_insert->execute()) {
        $_SESSION['msg_sucesso'] = "Conta criada com sucesso! Faça login para validar o acesso.";
    }

    header("Location: index.php");
    exit();
}
?>
