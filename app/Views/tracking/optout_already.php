<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Já Descadastrado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body text-center p-5">
                        <i class="fas fa-info-circle fa-4x text-info mb-4"></i>
                        <h2 class="mb-4">Já Descadastrado</h2>
                        <p class="text-muted mb-4">
                            O email <strong><?= esc($email) ?></strong> já está descadastrado.
                        </p>
                        <p class="text-muted">
                            Você não receberá mais emails nossos.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
