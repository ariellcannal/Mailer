<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Descadastrar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body text-center p-5">
                        <i class="fas fa-envelope-open-text fa-4x text-warning mb-4"></i>
                        <h2 class="mb-4">Descadastrar</h2>
                        <p class="text-muted mb-4">
                            Tem certeza que deseja parar de receber nossos emails?
                        </p>
                        <div class="alert alert-info">
                            <strong><?= esc($email) ?></strong>
                        </div>
                        <form method="POST" action="<?= base_url('optout/' . $hash) ?>">
                            <button type="submit" class="btn btn-danger btn-lg px-5">
                                <i class="fas fa-times-circle"></i> Sim, descadastrar
                            </button>
                            <a href="javascript:history.back()" class="btn btn-outline-secondary btn-lg px-5 ms-2">
                                Cancelar
                            </a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
