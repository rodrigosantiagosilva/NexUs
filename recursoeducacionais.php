<?php
// Conexão com o banco de dados
include "includes/conexao.php";
include "functions/recursos_functions.php";
include "includes/header.php";
?>

<style>
    .sidebar {
        width: 20%;
        position: fixed;
    }
    
    /* Filtros em linha */
    .filtros-inline {
        display: flex;
        gap: 15px;
        align-items: center;
        flex-wrap: wrap;
    }

    /* Grupos menores para alinhar bem */
    .filtro-group {
        display: flex;
        flex-direction: column;
        min-width: 180px;
    }

    /* Botões de ação */
    .filtro-acoes {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }

    /* Botão secundário estilo "Limpar" */
    .btn-secondary {
        background-color: #555;
        color: white;
        border: 1px solid #444;
    }
    .btn-secondary:hover {
        background-color: #666;
        }
</style>
<body>
<?php 
include 'includes/sidebar.php'
?>
    <div class="content-wrapper">
        <!-- Cabeçalho de recursos -->
        <div class="resources-header">
            <h1 class="h2 fw-bold">Recursos Educacionais</h1>

        </div>

        <!-- Seção de Filtros Unificada -->
        <div class="filtros-container" id="filtrosSection">
            <form method="GET" action="">
                <div class="filtros-body filtros-inline">
                    <!-- Filtro por Matéria -->
                    <div class="filtro-group">
                        <label class="filtro-label">Matéria</label>
                        <select class="form-select" name="materia">
                            <option value="">Todas as matérias</option>
                            <option value="matematica" <?= $materia === 'matematica' ? 'selected' : '' ?>>Matemática</option>
                            <option value="fisica" <?= $materia === 'fisica' ? 'selected' : '' ?>>Física</option>
                            <option value="geografia" <?= $materia === 'geografia'? 'selected' : '' ?>>Geografia</option>
                            <option value="filosofia" <?= $materia === 'filosofia' ? 'selected' : '' ?>>Filosofia</option>
                            <option value="historia" <?= $materia === 'historia' ? 'selected' : '' ?>>História</option>
                            <option value="lingua portuguesa" <?= $materia === 'lingua portuguesa' ? 'selected' : '' ?>>Língua Portuguesa</option>
                            <option value="ingles" <?= $materia === 'ingles' ? 'selected' : '' ?>>Inglês</option>
                            <option value="quimica" <?= $materia === 'quimica' ? 'selected' : '' ?>>Química</option>
                            <option value="sociologia" <?= $materia === 'sociologia' ? 'selected' : '' ?>>Sociologia</option>
                        </select>
                    </div>

                    <!-- Filtro por Formato -->
                    <div class="filtro-group">
                        <label class="filtro-label">Formato</label>
                        <select class="form-select" name="formato">
                            <option value="">Todos os formatos</option>
                            <option value="video" <?= $formato === 'video' ? 'selected' : '' ?>>Vídeo</option>
                            <option value="documento" <?= $formato === 'documento' ? 'selected' : '' ?>>Documento</option>
                            <option value="link" <?= $formato === 'link' ? 'selected' : '' ?>>Link</option>
                        </select>
                    </div>

                    <!-- Botões -->
                    <div class="filtro-acoes">
                        <button type="submit" class=" btn-primary">Aplicar</button>
                        <button type="button" class=" btn-primary" onclick="window.location.href='?'">Limpar</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Cards de Recursos -->
        <div class="row">
            <?php if (count($recursos) > 0): ?>
                <?php foreach ($recursos as $recurso): 
                    // Determinar ícone e cor com base no tipo
                    $icon_class = '';
                    $icon_color_class = '';
                    
                    switch ($recurso['tipo']) {
                        case 'documento':
                            $icon_class = 'bi-file-earmark-text';
                            $icon_color_class = 'icon-green';
                            break;
                        case 'video':
                            $icon_class = 'bi-camera-video';
                            $icon_color_class = 'icon-red';
                            break;
                        case 'link':
                            $icon_class = 'bi-link-45deg';
                            $icon_color_class = 'icon-blue';
                            break;
                        default:
                            $icon_class = 'bi-file-earmark';
                            $icon_color_class = 'icon-yellow';
                    }
                    
                    // Escapar dados para JavaScript
                    $js_url = htmlspecialchars($recurso['url'], ENT_QUOTES, 'UTF-8');
                    $js_title = htmlspecialchars($recurso['titulo'], ENT_QUOTES, 'UTF-8');
                ?>
                    <div class="col-md-6 mb-3">
                        <div class="resource-card">
                            <div class="card-header">
                                <div class="card-icon <?= $icon_color_class ?>">
                                    <i class="bi <?= $icon_class ?>"></i>
                                </div>
                                <div class="card-title-area">
                                    <h3 class="card-title"><?= htmlspecialchars($recurso['titulo']) ?></h3>
                                    <div>
                                        <span class="tag"><?= htmlspecialchars($recurso['tipo']) ?></span>
                                    </div>
                                </div>
                            </div>
                            <p class="card-description"><?= htmlspecialchars($recurso['descricao']) ?></p>
                            <p class="card-author">Autor: <?= htmlspecialchars($recurso['autor']) ?></p>
                            <p class="card-description">Matéria: <?= htmlspecialchars($recurso['materia']) ?></p>
                            <div class="card-stats">
                                <span>Publicado em: <?= date('d/m/Y', strtotime($recurso['data_publicacao'])) ?></span>
                            </div>
                            <div class="card-divider"></div>
                            <div class="card-actions">
                                <button class="action-btn btn-primary" onclick="window.open('<?= $js_url ?>', '_blank')">Abrir</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-md-12">
                    <div class="sem-resultados">
                        <p>Nenhum recurso encontrado com os filtros selecionados.</p>
                        <p>Tente ajustar os critérios de busca.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function shareResource(url, title) {
        if (navigator.share) {
            navigator.share({
                title: title,
                url: url
            })
            .catch(console.error);
        } else {
            // Fallback para copiar link
            navigator.clipboard.writeText(url).then(() => {
                alert('Link copiado: ' + url);
            });
        }
    }
    
    function toggleFiltros() {
        const filtrosSection = document.getElementById('filtrosSection');
        if (filtrosSection.style.display === 'none') {
            filtrosSection.style.display = 'block';
        } else {
            filtrosSection.style.display = 'none';
        }
    }
    </script>
</body>
</html>