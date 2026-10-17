<?php
/**
 * PRISMA-SLR - Dashboard
 * Visão geral do projeto ativo
 */
$pdb = getDB();
$hasProject = $projectId > 0;

// Contadores ACUMULADOS do funil (cada artigo conta em todas as etapas que já passou):
//   screened      = com decisão de triagem (título/resumo)
//   eligible      = aprovados na triagem (seguem para o texto completo)
//   elig_assessed = aprovados na triagem que já têm decisão de elegibilidade
//   included      = incluídos na elegibilidade
//   excluded      = excluídos na triagem + excluídos na elegibilidade
//   pending       = sem decisão de triagem
$stats = ['total_articles' => 0, 'total_duplicates' => 0, 'pending' => 0, 'screened' => 0,
          'eligible' => 0, 'elig_assessed' => 0, 'included' => 0, 'excluded' => 0];

$citationYear = date('Y');
$citationUrl = 'https://baseref.com.br/';
$citationAccessDate = date('d/m/Y');
$citationDoi = 'https://doi.org/10.5281/zenodo.21119457'; // codigo v1.0.0 arquivado no Zenodo
$citationAbnt = 'GROSS, Rafael; GONCALVES, Rodrigo Franco. BaseRef: plataforma de apoio a pesquisa e gestao de referencias bibliograficas. Versao 1.0.0 [software]. Zenodo, ' . $citationYear . '. DOI: ' . $citationDoi . '. Disponivel em: <' . $citationUrl . '>. Acesso em: ' . $citationAccessDate . '.';
$citationApa = 'Gross, R., & Goncalves, R. F. (' . $citationYear . '). BaseRef (Version 1.0.0) [Computer software]. Zenodo. ' . $citationDoi;
$dashboardPageContext = $projectId ? 'dashboard-project' : 'dashboard-home';
// Donation (PIX) settings come from config/donation.local.php (not versioned — see
// config/donation.example.php). Without a key, the donation buttons are hidden.
if (is_readable(__DIR__ . '/../config/donation.local.php')) require_once __DIR__ . '/../config/donation.local.php';
$pixKey = defined('DONATION_PIX_KEY') ? DONATION_PIX_KEY : '';
$pixMerchantName = defined('DONATION_PIX_NAME') ? DONATION_PIX_NAME : 'BASEREF';
$pixMerchantCity = defined('DONATION_PIX_CITY') ? DONATION_PIX_CITY : 'SAO PAULO';
$pixDescription = 'Doacao BaseRef';
$pixTxid = 'PRISMASLR';
$pixPayload = $pixKey !== '' ? buildPixPayload($pixKey, $pixMerchantName, $pixMerchantCity, $pixDescription, $pixTxid) : '';

if ($hasProject) {
    // Total e duplicatas continuam globais — não dependem de decisão de revisor
    $stmt = $pdb->prepare("
        SELECT COUNT(*) AS total_articles, SUM(is_duplicate) AS total_duplicates
        FROM articles WHERE project_id = ?
    ");
    $stmt->execute([$projectId]);
    $base = $stmt->fetch();
    $stats['total_articles']   = (int) ($base['total_articles']   ?? 0);
    $stats['total_duplicates'] = (int) ($base['total_duplicates'] ?? 0);

    // Progresso pessoal do revisor logado — mesma regra "mine" usada na Triagem,
    // para que cada revisor veja o que ELE já avaliou, não o status oficial do projeto.
    $reviewerId = (int) $user['id'];
    $stmt = $pdb->prepare("
        SELECT a.id AS article_id, sd.phase, sd.decision
        FROM articles a
        JOIN screening_decisions sd ON sd.article_id = a.id
        WHERE a.project_id = ? AND a.is_duplicate = 0
              AND sd.is_consensus = 0 AND (sd.reviewer_id = ? OR sd.reviewer_id IS NULL)
    ");
    $stmt->execute([$projectId, $reviewerId]);
    $decisionsByArticle = [];
    foreach ($stmt->fetchAll() as $row) {
        $decisionsByArticle[$row['article_id']][$row['phase']] = $row['decision'];
    }

    $stmt = $pdb->prepare("SELECT id FROM articles WHERE project_id = ? AND is_duplicate = 0");
    $stmt->execute([$projectId]);
    $nonDupIds = array_column($stmt->fetchAll(), 'id');

    foreach ($nonDupIds as $id) {
        $d         = $decisionsByArticle[$id] ?? [];
        $screenDec = $d['screening']   ?? null;
        $eligDec   = $d['eligibility'] ?? null;

        // Antes cada artigo caía numa única "situação atual" (só os 'uncertain' contavam
        // como triados e só os aprovados ainda sem leitura completa como elegíveis), e as
        // barras de progresso dividiam esses grupos excludentes entre si — num projeto
        // concluído isso dava "Triagem 0/N", "Elegibilidade 0/0" e "Incluídos X/0".
        if ($screenDec === null) {
            $stats['pending']++;
            continue;
        }
        $stats['screened']++;
        if ($screenDec === 'exclude') {
            $stats['excluded']++;
            continue;
        }
        if ($screenDec !== 'include') continue;   // 'uncertain': triado, ainda sem desfecho

        $stats['eligible']++;
        if ($eligDec === null) continue;
        $stats['elig_assessed']++;
        if ($eligDec === 'include')     $stats['included']++;
        elseif ($eligDec === 'exclude') $stats['excluded']++;
    }
}
?>

<!-- Modais globais (citação, feedback, doação) — sempre renderizados -->
<div class="modal-overlay" id="citation-modal">
  <div class="modal" style="max-width:760px">
    <div class="modal-header">
      <h2 class="modal-title"><i class="fa fa-quote-left" style="color:var(--primary);margin-right:8px"></i>Como citar o BaseRef</h2>
      <button class="btn btn-ghost btn-sm" type="button" onclick="closeCitationModal()"><i class="fa fa-times"></i></button>
    </div>
    <div class="modal-body">
      <p style="font-size:.84rem;color:var(--text-muted);line-height:1.7;margin:0 0 16px">
        BaseRef e um software livre desenvolvido por Rafael Gross e Rodrigo Franco Goncalves. Abaixo estao sugestoes de citacao em ABNT e APA para uso em artigos, TCCs, dissertacoes e teses.
      </p>
      <div style="display:grid;gap:16px">
        <div style="border:1px solid var(--border);border-radius:12px;padding:16px;background:var(--bg-card2)">
          <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:10px;flex-wrap:wrap">
            <strong style="font-size:.86rem">ABNT</strong>
            <button class="btn btn-primary btn-sm" type="button" onclick="copyDashboardCitation('abnt')"><i class="fa fa-copy"></i> Copiar ABNT</button>
          </div>
          <textarea class="form-control" id="citation-abnt-text" rows="4" readonly><?= htmlspecialchars($citationAbnt) ?></textarea>
        </div>
        <div style="border:1px solid var(--border);border-radius:12px;padding:16px;background:var(--bg-card2)">
          <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:10px;flex-wrap:wrap">
            <strong style="font-size:.86rem">APA</strong>
            <button class="btn btn-primary btn-sm" type="button" onclick="copyDashboardCitation('apa')"><i class="fa fa-copy"></i> Copiar APA</button>
          </div>
          <textarea class="form-control" id="citation-apa-text" rows="4" readonly><?= htmlspecialchars($citationApa) ?></textarea>
        </div>
      </div>
      <div style="margin-top:14px;padding:12px 14px;border-radius:10px;background:rgba(0,212,255,.08);border:1px solid rgba(0,212,255,.18);font-size:.78rem;color:var(--text-muted);line-height:1.6">
        <i class="fa fa-circle-info" style="color:var(--primary);margin-right:6px"></i>
        O projeto e distribuido como software livre (licenca MIT) e esta apresentado publicamente em <?= htmlspecialchars($citationUrl) ?>. O codigo-fonte da versao 1.0.0 esta arquivado no Zenodo com DOI permanente: <a href="<?= htmlspecialchars($citationDoi) ?>" target="_blank" rel="noopener" style="color:var(--primary)">10.5281/zenodo.21119457</a>.
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" type="button" onclick="closeCitationModal()">Fechar</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="feedback-modal">
  <div class="modal" style="max-width:720px">
    <div class="modal-header">
      <h2 class="modal-title"><i class="fa fa-star" style="color:var(--primary);margin-right:8px"></i>Avaliação do sistema</h2>
      <button class="btn btn-ghost btn-sm" type="button" onclick="closeFeedbackModal()"><i class="fa fa-times"></i></button>
    </div>
    <div class="modal-body">
      <p style="font-size:.84rem;color:var(--text-muted);line-height:1.7;margin:0 0 16px">
        Queremos saber sua opiniao sobre o BaseRef. Deixe uma nota e, se quiser, escreva uma sugestao de melhoria.
      </p>
      <div class="form-group">
        <label class="form-label">Sua nota</label>
        <div id="feedback-rating" style="display:flex;gap:8px;flex-wrap:wrap">
          <button type="button" class="btn btn-ghost btn-sm" data-rating="1" onclick="setFeedbackRating(1)">1</button>
          <button type="button" class="btn btn-ghost btn-sm" data-rating="2" onclick="setFeedbackRating(2)">2</button>
          <button type="button" class="btn btn-ghost btn-sm" data-rating="3" onclick="setFeedbackRating(3)">3</button>
          <button type="button" class="btn btn-ghost btn-sm" data-rating="4" onclick="setFeedbackRating(4)">4</button>
          <button type="button" class="btn btn-ghost btn-sm" data-rating="5" onclick="setFeedbackRating(5)">5</button>
        </div>
        <div id="feedback-rating-label" style="font-size:.78rem;color:var(--text-muted);margin-top:8px">Selecione uma nota de 1 a 5.</div>
      </div>
      <div class="form-group">
        <label class="form-label" for="feedback-suggestion">Sugestão ou comentário</label>
        <textarea class="form-control" id="feedback-suggestion" rows="5" maxlength="3000" placeholder="O que voce achou do sistema? O que podemos melhorar?"></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" type="button" onclick="closeFeedbackModal()">Cancelar</button>
      <button class="btn btn-primary" type="button" id="feedback-submit-btn" onclick="submitFeedback()"><i class="fa fa-paper-plane"></i> Enviar avaliação</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="donation-modal">
  <div class="modal" style="max-width:760px">
    <div class="modal-header">
      <h2 class="modal-title"><i class="fa fa-heart" style="color:#ef4444;margin-right:8px"></i>Apoie o BaseRef</h2>
      <button class="btn btn-ghost btn-sm" type="button" onclick="closeDonationModal()"><i class="fa fa-times"></i></button>
    </div>
    <div class="modal-body">
      <p style="font-size:.92rem;line-height:1.7;margin:0 0 12px;font-weight:600">Gostando do sistema? Sua doacao ajuda a manter o site no ar, evoluir o BaseRef e sustentar este projeto de software livre.</p>
      <p style="font-size:.82rem;color:var(--text-muted);line-height:1.7;margin:0 0 18px">O apoio e voluntario. Se quiser contribuir, use o Pix abaixo.</p>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:18px;align-items:start">
        <div style="border:1px solid var(--border);border-radius:14px;padding:18px;background:var(--bg-card2);text-align:center">
          <div id="pix-qrcode" style="display:flex;justify-content:center;align-items:center;min-height:240px"></div>
          <div style="font-size:.74rem;color:var(--text-muted);margin-top:10px">Escaneie com o app do seu banco</div>
        </div>
        <div style="display:flex;flex-direction:column;gap:12px">
          <div style="border:1px solid var(--border);border-radius:12px;padding:14px;background:var(--bg-card2)">
            <div style="font-size:.74rem;color:var(--text-muted);margin-bottom:6px">Codigo Pix copia e cola</div>
            <button class="btn btn-primary btn-sm" type="button" style="margin-top:10px" onclick="copyDashboardPixCode()"><i class="fa fa-copy"></i> Copiar codigo Pix</button>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" type="button" onclick="closeDonationModal()">Fechar</button>
    </div>
  </div>
</div>

<!-- Convite discreto para citar o BaseRef — aparece de tempos em tempos, não bloqueia a tela -->
<div id="cite-nudge" style="display:none;position:fixed;bottom:20px;right:20px;z-index:9000;max-width:340px;
     background:var(--bg-card);border:1px solid var(--border);border-radius:14px;
     box-shadow:0 12px 32px rgba(0,0,0,.25);padding:18px 20px;animation:citeNudgeIn .35s ease">
  <button type="button" onclick="dismissCiteNudge()" aria-label="Fechar"
          style="position:absolute;top:8px;right:10px;background:none;border:none;cursor:pointer;
                 color:var(--text-muted);font-size:1rem;line-height:1;padding:4px">
    <i class="fa fa-times"></i>
  </button>
  <div style="display:flex;gap:12px;align-items:flex-start">
    <i class="fa fa-quote-left" style="color:var(--primary);font-size:1.3rem;margin-top:2px"></i>
    <div>
      <div style="font-weight:700;font-size:.9rem;margin-bottom:6px">Usando o BaseRef na sua pesquisa?</div>
      <div style="font-size:.8rem;color:var(--text-muted);line-height:1.55;margin-bottom:14px">
        Considere citar o sistema no seu artigo, TCC ou dissertação. Isso nos ajuda a continuar
        melhorando e divulgando a ferramenta para outros pesquisadores.
      </div>
      <div style="display:flex;gap:8px">
        <button type="button" class="btn btn-primary btn-sm" onclick="openCitationModalFromNudge()">
          <i class="fa fa-quote-left"></i> Ver como citar
        </button>
        <button type="button" class="btn btn-ghost btn-sm" onclick="dismissCiteNudge()">Agora não</button>
      </div>
    </div>
  </div>
</div>
<style>
@keyframes citeNudgeIn {
  from { opacity: 0; transform: translateY(12px); }
  to   { opacity: 1; transform: translateY(0); }
}
</style>
<script>
(function () {
  var CITE_NUDGE_KEY = 'baseref_cite_nudge_dismissed_at';
  var CITE_NUDGE_INTERVAL_DAYS = 14;

  function citeNudgeShouldShow() {
    var last = localStorage.getItem(CITE_NUDGE_KEY);
    if (!last) return true;
    var elapsedDays = (Date.now() - parseInt(last, 10)) / (1000 * 60 * 60 * 24);
    return elapsedDays >= CITE_NUDGE_INTERVAL_DAYS;
  }

  window.dismissCiteNudge = function () {
    localStorage.setItem(CITE_NUDGE_KEY, String(Date.now()));
    var el = document.getElementById('cite-nudge');
    if (el) el.style.display = 'none';
  };

  window.openCitationModalFromNudge = function () {
    dismissCiteNudge();
    openCitationModal();
  };

  if (citeNudgeShouldShow()) {
    setTimeout(function () {
      var el = document.getElementById('cite-nudge');
      if (el) el.style.display = 'block';
    }, 4000);
  }
})();
</script>

<!-- Se não há projeto selecionado, mostra boas-vindas -->
<?php if (!$hasProject): ?>
<style>
.welcome-hero {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 60vh;
  padding: 40px 20px;
  text-align: center;
  width: 100%;
  overflow-x: clip;
}
.welcome-logo {
  width: 200px;
  height: auto;
  margin-bottom: 32px;
}
.welcome-title {
  font-size: 2.2rem;
  font-weight: 800;
  color: var(--text-primary);
  margin-bottom: 12px;
  letter-spacing: -0.5px;
}
.welcome-title span {
  color: var(--primary);
}
.welcome-desc {
  color: var(--text-secondary);
  font-size: 1.05rem;
  line-height: 1.75;
  max-width: 480px;
  margin: 0 auto 36px;
}
.welcome-actions {
  display: flex;
  gap: 14px;
  justify-content: center;
  flex-wrap: wrap;
  margin-bottom: 60px;
}
.welcome-actions .btn-primary {
  padding: 13px 32px;
  font-size: 1rem;
  font-weight: 700;
  border-radius: 10px;
  box-shadow: 0 4px 18px rgba(var(--primary-rgb, 59,130,246), 0.35);
}
.welcome-actions .btn-outline {
  padding: 13px 28px;
  font-size: 1rem;
  border-radius: 10px;
}
.welcome-divider {
  width: 100%;
  max-width: 780px;
  border: none;
  border-top: 1px solid var(--border);
  margin-bottom: 48px;
}
.welcome-features {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  width: 100%;
  max-width: 860px;
}
.welcome-feature-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 28px 20px;
  text-align: center;
  transition: box-shadow 0.2s, transform 0.2s;
}
.welcome-feature-card:hover {
  box-shadow: 0 8px 28px rgba(0,0,0,0.08);
  transform: translateY(-3px);
}
.welcome-feature-icon {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  background: linear-gradient(135deg, var(--primary-light, #eff6ff), var(--primary-lighter, #dbeafe));
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 16px;
  font-size: 1.3rem;
  color: var(--primary);
}
.welcome-feature-title {
  font-weight: 700;
  font-size: 0.95rem;
  color: var(--text-primary);
  margin-bottom: 8px;
}
.welcome-feature-desc {
  font-size: 0.8rem;
  color: var(--text-muted);
  line-height: 1.6;
}
.welcome-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 999px;
  padding: 5px 14px;
  font-size: 0.78rem;
  color: var(--text-muted);
  margin-bottom: 28px;
  font-weight: 500;
}
.welcome-badge i { color: var(--accent-green, #22c55e); }

.dashboard-header-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

@media (max-width: 768px) {
  .welcome-hero {
    min-height: auto;
    padding: 24px 0 8px;
  }

  .welcome-logo {
    width: min(180px, 70vw);
    margin-bottom: 24px;
  }

  .welcome-title {
    font-size: 1.7rem;
  }

  .welcome-desc {
    font-size: 0.98rem;
    line-height: 1.65;
    margin-bottom: 28px;
  }

  .welcome-actions {
    width: 100%;
    margin-bottom: 40px;
  }

  .welcome-actions .btn-primary,
  .welcome-actions .btn-outline {
    width: 100%;
    justify-content: center;
  }

  .welcome-divider {
    margin-bottom: 32px;
  }

  .welcome-features {
    gap: 16px;
  }

  .dashboard-header-actions {
    width: 100%;
  }

  .dashboard-header-actions .btn {
    flex: 1 1 220px;
    justify-content: center;
  }
}

@media (max-width: 520px) {
  .welcome-hero {
    padding-left: 0;
    padding-right: 0;
  }

  .welcome-features {
    grid-template-columns: 1fr;
  }

  .welcome-feature-card {
    padding: 22px 16px;
  }

  .dashboard-header-actions .btn {
    width: 100%;
    flex-basis: 100%;
  }
}
</style>

<div class="welcome-hero">
  <span class="welcome-badge">
    <i class="fa fa-circle-check"></i> Open Source · PRISMA 2020 · v1.0
  </span>

  <img src="assets/img/baseref_dark.png" alt="BaseRef" class="welcome-logo" id="dashLogo">

  <p class="welcome-desc">
    Gerencie suas Revisões Sistemáticas da Literatura com rigor metodológico,
    do protocolo à publicação — tudo em um único sistema.
  </p>

  <div class="welcome-actions">
    <a href="?page=projects" class="btn btn-primary btn-lg">
      <i class="fa fa-plus"></i> Criar Projeto
    </a>
    <?php if (!empty($projectList)): ?>
    <a href="?page=projects" class="btn btn-outline btn-lg">
      <i class="fa fa-folder-open"></i> Abrir Projeto
    </a>
    <?php endif; ?>
    <button type="button" class="btn btn-outline btn-lg" onclick="openCitationModal()">
      <i class="fa fa-quote-left"></i> Como citar
    </button>
    <button type="button" class="btn btn-outline btn-lg" onclick="openFeedbackModal()">
      <i class="fa fa-star"></i> Avaliar sistema
    </button>
    <?php if ($pixKey !== ''): ?>
    <button type="button" class="btn btn-outline btn-lg" onclick="openDonationModal()">
      <i class="fa fa-heart"></i> Apoiar projeto
    </button>
    <?php endif; ?>
  </div>

  <hr class="welcome-divider">

  <div class="welcome-features">
    <?php
    $features = [
      ['fa-file-import',      'Importação',              'Suporte a .bib do Scopus, Web of Science e outras bases.'],
      ['fa-copy',             'Deduplicação',             'Detecção automática de duplicatas por DOI e título.'],
      ['fa-magnifying-glass', 'Triagem',                  'Avaliação de título e resumo com decisões configuráveis.'],
      ['fa-check-double',     'Elegibilidade',            'Avaliação de texto completo com rastreabilidade.'],
      ['fa-robot',            'Triagem com IA',           'Inteligência artificial analisa o texto e sugere inclusão ou exclusão com base nos critérios definidos.'],
      ['fa-list-check',       'Método PICO',              'Extração estruturada de População, Intervenção, Comparação e Desfecho diretamente nos artigos.'],
      ['fa-users',            'Colaboração em equipe',    'Múltiplos pesquisadores no mesmo projeto com controle de permissões e histórico de decisões.'],
      ['fa-handshake',        'Concordância inter-avaliadores', 'Cálculo automático do Kappa de Cohen (κ) para medir o acordo entre avaliadores.'],
      ['fa-shield-halved',    'Qualidade e Viés',         'Avaliação de qualidade metodológica e risco de viés dos estudos incluídos.'],
      ['fa-diagram-project',  'Diagrama PRISMA',          'Gerado automaticamente com exportação PNG/SVG.'],
      ['fa-chart-bar',        'Bibliometria',             '12 gráficos + redes de co-autoria e keywords.'],
      ['fa-file-export',      'Exportação',               'Exporte resultados em CSV, BibTeX e relatórios PDF prontos para publicação.'],
    ];
    foreach ($features as $f): ?>
    <div class="welcome-feature-card">
      <div class="welcome-feature-icon">
        <i class="fa <?= $f[0] ?>"></i>
      </div>
      <div class="welcome-feature-title"><?= $f[1] ?></div>
      <div class="welcome-feature-desc"><?= $f[2] ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<script>
function openCitationModal() {
  var m = document.getElementById('citation-modal');
  if (m) m.classList.add('open');
}
function closeCitationModal() {
  var m = document.getElementById('citation-modal');
  if (m) m.classList.remove('open');
}
function openFeedbackModal() {
  var m = document.getElementById('feedback-modal');
  if (m) m.classList.add('open');
}
function closeFeedbackModal() {
  var m = document.getElementById('feedback-modal');
  if (m) m.classList.remove('open');
}
var DASH_PIX_QR_READY = false;
function openDonationModal() {
  var m = document.getElementById('donation-modal');
  if (m) m.classList.add('open');
  renderDonationQr();
}
function closeDonationModal() {
  var m = document.getElementById('donation-modal');
  if (m) m.classList.remove('open');
}
function copyDashboardCitation(format) {
  var field = document.getElementById(format === 'apa' ? 'citation-apa-text' : 'citation-abnt-text');
  if (!field) return;
  copyToClipboard(field.value);
}
function renderDonationQr() {
  if (DASH_PIX_QR_READY) return;
  var target = document.getElementById('pix-qrcode');
  if (!target) return;
  var pixPayload = <?= json_encode($pixPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  if (typeof QRCode === 'undefined') {
    target.innerHTML = '<div style="font-size:.82rem;color:var(--text-muted);line-height:1.6">QR Code indisponivel.<br>Use o codigo Pix copia e cola.</div>';
    return;
  }
  target.innerHTML = '';
  new QRCode(target, { text: pixPayload, width: 220, height: 220, correctLevel: QRCode.CorrectLevel.M });
  DASH_PIX_QR_READY = true;
}
function copyDashboardPixCode() {
  copyToClipboard(<?= json_encode($pixPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>);
}
var DASH_FEEDBACK_RATING = 0;
var DASH_FEEDBACK_LABELS = {1:'Muito ruim',2:'Ruim',3:'Regular',4:'Bom',5:'Excelente'};
function setFeedbackRating(rating) {
  DASH_FEEDBACK_RATING = rating;
  var label = document.getElementById('feedback-rating-label');
  if (label) label.textContent = rating + '/5 - ' + (DASH_FEEDBACK_LABELS[rating] || '');
  document.querySelectorAll('#feedback-rating [data-rating]').forEach(function(btn) {
    var active = Number(btn.dataset.rating) === rating;
    btn.classList.toggle('btn-primary', active);
    btn.classList.toggle('btn-ghost', !active);
  });
}
</script>

<?php else: ?>

<!-- ====== Dashboard com projeto ====== -->
<div class="page-header">
  <div>
    <h1 class="page-title"><i class="fa fa-house"></i> Dashboard</h1>
    <p class="page-subtitle"><?= htmlspecialchars($currentProject['title']) ?></p>
  </div>
  <div class="dashboard-header-actions">
    <a href="?page=import&project_id=<?= $projectId ?>" class="btn btn-primary">
      <i class="fa fa-file-import"></i> Importar .bib
    </a>
    <a href="?page=prisma-flow&project_id=<?= $projectId ?>" class="btn btn-outline">
      <i class="fa fa-diagram-project"></i> PRISMA Flow
    </a>
    <button type="button" class="btn btn-outline" onclick="openCitationModal()">
      <i class="fa fa-quote-left"></i> Como citar
    </button>
    <button type="button" class="btn btn-outline" onclick="openFeedbackModal()">
      <i class="fa fa-star"></i> Avaliar sistema
    </button>
    <?php if ($pixKey !== ''): ?>
    <button type="button" class="btn btn-outline" onclick="openDonationModal()">
      <i class="fa fa-heart"></i> Apoiar projeto
    </button>
    <?php endif; ?>
  </div>
</div>

<!-- Stats -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon"><i class="fa fa-book"></i></div>
    <div class="stat-value" id="stat-total"><?= number_format((int)$stats['total_articles']) ?></div>
    <div class="stat-label">Total identificado</div>
  </div>
  <div class="stat-card red">
    <div class="stat-icon"><i class="fa fa-copy"></i></div>
    <div class="stat-value" id="stat-dup"><?= number_format((int)$stats['total_duplicates']) ?></div>
    <div class="stat-label">Duplicatas</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon"><i class="fa fa-clock"></i></div>
    <div class="stat-value" id="stat-pending"><?= number_format((int)$stats['pending']) ?></div>
    <div class="stat-label">Pendentes (minha triagem)</div>
  </div>
  <div class="stat-card yellow">
    <div class="stat-icon"><i class="fa fa-magnifying-glass"></i></div>
    <div class="stat-value" id="stat-screened"><?= number_format((int)$stats['screened']) ?></div>
    <div class="stat-label"><span data-tx="dash_screened" translate="no" class="notranslate">Triados</span></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon"><i class="fa fa-check-double"></i></div>
    <div class="stat-value"><?= number_format((int)$stats['eligible']) ?></div>
    <div class="stat-label">Elegíveis</div>
  </div>
  <div class="stat-card green">
    <div class="stat-icon"><i class="fa fa-circle-check"></i></div>
    <div class="stat-value"><?= number_format((int)$stats['included']) ?></div>
    <div class="stat-label">Incluídos</div>
  </div>
  <div class="stat-card red">
    <div class="stat-icon"><i class="fa fa-ban"></i></div>
    <div class="stat-value"><?= number_format((int)$stats['excluded']) ?></div>
    <div class="stat-label">Excluídos</div>
  </div>
</div>

<!-- Fase atual e ações rápidas -->
<div class="grid-2" style="margin-bottom:20px">
  <!-- Progresso do processo -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa fa-timeline"></i> Progresso do Processo</div>
    </div>
    <div style="display:flex;flex-direction:column;gap:16px">
      <?php
      $total = max(1, (int)$stats['total_articles']);
      $nonDup = max(0, $total - (int)$stats['total_duplicates']);
      $phases = [
        ['Identificação',  $total,               $total,                  'var(--primary)',        'fa-book'],
        ['Deduplicação',   $nonDup,               $total,                  'var(--accent-red)',     'fa-copy'],
        // Triagem: triados / únicos · Elegibilidade: avaliados no texto completo / aprovados
        // na triagem · Incluídos: incluídos / avaliados no texto completo
        ['Triagem',        (int)$stats['screened'],      (int)$nonDup,               'var(--accent-yellow)',  'fa-magnifying-glass'],
        ['Elegibilidade',  (int)$stats['elig_assessed'], (int)$stats['eligible'],    'var(--primary)',        'fa-check-double'],
        ['Incluídos',      (int)$stats['included'],      (int)$stats['elig_assessed'],'var(--accent-green)',  'fa-circle-check'],
      ];
      foreach ($phases as [$label, $n, $d, $color, $icon]):
        $pct = $d > 0 ? min(100, round($n / $d * 100)) : 0;
      ?>
      <div>
        <div style="display:flex;justify-content:space-between;margin-bottom:5px;font-size:0.8rem">
          <span><i class="fa <?= $icon ?>" style="color:<?= $color ?>;margin-right:6px"></i><?= $label ?></span>
          <span style="color:var(--text-muted)"><?= $n ?> / <?= $d ?></span>
        </div>
        <div class="progress-bar">
          <div class="progress-fill" style="width:<?= $pct ?>%;background:<?= $color ?>"></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Ações rápidas -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa fa-bolt"></i> Ações Rápidas</div>
    </div>
    <div style="display:flex;flex-direction:column;gap:8px">
      <?php
      $actions = [
        ['import',       'fa-file-import',       'btn-outline',  'Importar arquivo .bib'],
        ['duplicates',   'fa-copy',               'btn-outline',  'Gerenciar duplicatas'],
        ['screening',    'fa-magnifying-glass',   'btn-outline',  'Continuar triagem'],
        ['eligibility',  'fa-check-double',       'btn-outline',  'Avaliar elegibilidade'],
        ['bibliometrics','fa-chart-bar',           'btn-outline',  'Análise bibliométrica'],
        ['prisma-flow',  'fa-diagram-project',    'btn-outline',  'Ver diagrama PRISMA'],
        ['export',       'fa-upload',             'btn-outline',  'Exportar dados'],
        ['checklist',    'fa-list-check',          'btn-outline',  'Checklist PRISMA 2020'],
      ];
      foreach ($actions as [$pg, $icon, $cls, $label]): ?>
      <a href="?page=<?= $pg ?>&project_id=<?= $projectId ?>"
         class="btn <?= $cls ?>" style="justify-content:flex-start">
        <i class="fa <?= $icon ?>"></i> <?= $label ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Equipe do projeto -->
<div class="card" id="dashboard-team" style="margin-bottom:20px">
  <div class="card-header">
    <div class="card-title"><i class="fa fa-users"></i> Equipe do Projeto</div>
  </div>
  <p style="font-size:.8rem;color:var(--text-muted);margin:0 0 14px;line-height:1.6">
    Convide colaboradores pelo e-mail da conta deles. O sistema nao lista usuarios cadastrados; se o e-mail ainda nao existir,
    sera exibida a orientacao para a pessoa entrar no sistema pelo menos uma vez.
  </p>
  <p style="font-size:.78rem;color:var(--text-muted);margin:0 0 14px;line-height:1.6">
    <b>Revisor cego</b>: triagem independente; vê apenas as proprias decisoes e entra no kappa de Cohen.
    <b>Revisor de apoio</b>: vê o que os outros revisores decidiram para ajudar na escolha; nao entra no kappa nem no status oficial.
  </p>
  <div id="dashboard-team-members">
    <div class="text-center" style="padding:24px"><div class="spinner"></div></div>
  </div>
  <div id="dashboard-team-add" style="display:none;gap:8px;margin-top:12px;flex-wrap:wrap">
    <input type="email" class="form-control" id="dashboard-member-email"
           placeholder="email do colaborador" style="flex:1;min-width:220px">
    <select class="form-control" id="dashboard-member-role" style="width:auto">
      <option value="reviewer">Revisor cego</option>
      <option value="support">Revisor de apoio</option>
    </select>
    <button class="btn btn-primary btn-sm" type="button" onclick="addProjectMember()">
      <i class="fa fa-user-plus"></i> Adicionar colaborador
    </button>
  </div>
</div>

<!-- Fontes de busca -->
<div class="card" id="dashboard-sources">
  <div class="card-header">
    <div class="card-title"><i class="fa fa-database"></i> Fontes de Busca</div>
    <a href="?page=import&project_id=<?= $projectId ?>" class="btn btn-ghost btn-sm">
      <i class="fa fa-plus"></i> Adicionar
    </a>
  </div>
  <div id="sources-table-container">
    <div class="text-center" style="padding:30px"><div class="spinner"></div></div>
  </div>
</div>


<script>
let DASH_IS_PROJECT_OWNER = false;
let DASH_FEEDBACK_RATING = 0;
let DASH_PIX_QR_READY = false;
const DASH_FEEDBACK_LABELS = {
  1: 'Muito ruim',
  2: 'Ruim',
  3: 'Regular',
  4: 'Bom',
  5: 'Excelente',
};

function openCitationModal() {
  const modal = document.getElementById('citation-modal');
  if (modal) modal.classList.add('open');
}

function closeCitationModal() {
  const modal = document.getElementById('citation-modal');
  if (modal) modal.classList.remove('open');
}

function copyDashboardCitation(format) {
  const field = document.getElementById(format === 'apa' ? 'citation-apa-text' : 'citation-abnt-text');
  if (!field) return;
  copyToClipboard(field.value);
}

function openDonationModal() {
  const modal = document.getElementById('donation-modal');
  if (!modal) return;
  modal.classList.add('open');
  renderDonationQr();
}

function closeDonationModal() {
  const modal = document.getElementById('donation-modal');
  if (!modal) return;
  modal.classList.remove('open');
}

function renderDonationQr() {
  if (DASH_PIX_QR_READY) return;
  const target = document.getElementById('pix-qrcode');
  if (!target) return;

  const pixPayload = <?= json_encode($pixPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

  if (typeof QRCode === 'undefined') {
    target.innerHTML = '<div style="font-size:.82rem;color:var(--text-muted);line-height:1.6">QR Code indisponivel no momento.<br>Use o codigo Pix copia e cola ao lado.</div>';
    return;
  }

  target.innerHTML = '';
  new QRCode(target, {
    text: pixPayload,
    width: 220,
    height: 220,
    correctLevel: QRCode.CorrectLevel.M,
  });
  DASH_PIX_QR_READY = true;
}

function copyDashboardPixCode() {
  copyToClipboard(<?= json_encode($pixPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>);
}

function openFeedbackModal() {
  const modal = document.getElementById('feedback-modal');
  if (!modal) return;
  modal.classList.add('open');
}

function closeFeedbackModal() {
  const modal = document.getElementById('feedback-modal');
  if (!modal) return;
  modal.classList.remove('open');
}

function setFeedbackRating(rating) {
  DASH_FEEDBACK_RATING = rating;
  const label = document.getElementById('feedback-rating-label');
  if (label) label.textContent = `${rating}/5 - ${DASH_FEEDBACK_LABELS[rating] || ''}`;

  document.querySelectorAll('#feedback-rating [data-rating]').forEach(btn => {
    const active = Number(btn.dataset.rating) === rating;
    btn.classList.toggle('btn-primary', active);
    btn.classList.toggle('btn-ghost', !active);
  });
}

async function submitFeedback() {
  if (!DASH_FEEDBACK_RATING) {
    showToast('Selecione uma nota de 1 a 5', 'warning');
    return;
  }

  const btn = document.getElementById('feedback-submit-btn');
  const suggestion = document.getElementById('feedback-suggestion').value.trim();

  btn.disabled = true;
  btn.innerHTML = '<div class="spinner"></div>';

  try {
    const r = await fetch('api/feedback.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        rating: DASH_FEEDBACK_RATING,
        suggestion,
        page: '<?= $dashboardPageContext ?>',
        project_id: <?= $projectId ?: 'null' ?>,
      }),
    });
    const d = await r.json();
    if (d.error) throw new Error(d.message);

    showToast(d.message || 'Feedback enviado', 'success');
    document.getElementById('feedback-suggestion').value = '';
    setFeedbackRating(0);
    const label = document.getElementById('feedback-rating-label');
    if (label) label.textContent = 'Selecione uma nota de 1 a 5.';
    closeFeedbackModal();
  } catch (e) {
    showToast(e.message || 'Erro ao enviar feedback', 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="fa fa-paper-plane"></i> Enviar avaliação';
  }
}

async function loadProjectTeam() {
  const membersEl = document.getElementById('dashboard-team-members');
  const addRowEl = document.getElementById('dashboard-team-add');

  try {
    const r = await fetch(`api/kappa.php?project_id=<?= $projectId ?>&phase=screening`);
    const d = await r.json();
    if (d.error) throw new Error(d.message);

    DASH_IS_PROJECT_OWNER = !!d.is_owner;
    addRowEl.style.display = DASH_IS_PROJECT_OWNER ? 'flex' : 'none';

    const members = Array.isArray(d.members) ? d.members : [];
    if (!members.length) {
      membersEl.innerHTML = '<p style="font-size:.82rem;color:var(--text-muted)">Nenhum colaborador cadastrado.</p>';
      return;
    }

    membersEl.innerHTML = members.map(member => `
      <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:8px 0;border-bottom:1px solid var(--border);flex-wrap:wrap">
        <div style="font-size:.84rem;min-width:240px">
          <i class="fa ${member.role === 'owner' ? 'fa-crown' : 'fa-user'}" style="color:var(--primary);width:18px"></i>
          ${escHtml(member.name)} <small class="text-muted">(${escHtml(member.email)})</small>
        </div>
        ${member.role !== 'owner' && DASH_IS_PROJECT_OWNER
          ? `<div style="display:flex;gap:8px;align-items:center">
               <select class="form-control" style="width:auto;font-size:.78rem;padding:4px 8px" onchange="setMemberRole(${member.id}, this.value)">
                 <option value="reviewer" ${member.role === 'reviewer' ? 'selected' : ''}>Revisor cego</option>
                 <option value="support" ${member.role === 'support' ? 'selected' : ''}>Revisor de apoio</option>
               </select>
               <button class="btn btn-ghost btn-sm" type="button" onclick="removeProjectMember(${member.id})"><i class="fa fa-xmark"></i> Remover</button>
             </div>`
          : `<span class="badge badge-pending" style="font-size:.68rem">${member.role === 'owner' ? 'dono' : member.role === 'support' ? 'apoio' : 'revisor cego'}</span>`}
      </div>`).join('');
  } catch (e) {
    membersEl.innerHTML = `<div class="alert alert-error"><i class="fa fa-triangle-exclamation"></i> ${escHtml(e.message)}</div>`;
  }
}

async function addProjectMember() {
  const input = document.getElementById('dashboard-member-email');
  const email = input.value.trim();
  if (!email) return;

  try {
    const r = await fetch('api/kappa.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'add_member', project_id: <?= $projectId ?>, email,
                             role: document.getElementById('dashboard-member-role').value }),
    });
    const d = await r.json();
    if (d.error) throw new Error(d.message);

    input.value = '';
    showToast(d.message || 'Colaborador adicionado', 'success');
    loadProjectTeam();
  } catch (e) {
    showToast(e.message, 'error');
  }
}

async function setMemberRole(userId, role) {
  try {
    const r = await fetch('api/kappa.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'set_role', project_id: <?= $projectId ?>, user_id: userId, role }),
    });
    const d = await r.json();
    if (d.error) throw new Error(d.message);
    showToast(d.message || 'Papel atualizado', 'success');
  } catch (e) {
    showToast(e.message, 'error');
  }
  loadProjectTeam();
}

async function removeProjectMember(userId) {
  if (!confirm('Remover este colaborador do projeto?')) return;

  try {
    const r = await fetch('api/kappa.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'remove_member', project_id: <?= $projectId ?>, user_id: userId }),
    });
    const d = await r.json();
    if (d.error) throw new Error(d.message);

    showToast(d.message || 'Colaborador removido', 'success');
    loadProjectTeam();
  } catch (e) {
    showToast(e.message, 'error');
  }
}

// Carrega fontes do projeto no dashboard
(async () => {
  const container = document.getElementById('sources-table-container');
  try {
    const r = await fetch(`api/import.php?project_id=<?= $projectId ?>`);
    const sources = await r.json();
    if (!sources.length) {
      container.innerHTML = '<div class="empty-state"><i class="fa fa-inbox"></i><p>Nenhum arquivo importado ainda.</p><small>Use o botão "Importar .bib" para começar.</small></div>';
      return;
    }
    let html = '<div class="table-wrapper"><table><thead><tr>'
      + '<th>Fonte</th><th>Tipo</th><th>Artigos</th><th>Data da busca</th>'
      + '<th>Importado em</th></tr></thead><tbody>';
    sources.forEach(s => {
      html += `<tr>
        <td><strong>${escHtml(s.name)}</strong><br><small class="text-muted">${escHtml(s.file_name || '')}</small></td>
        <td><span class="badge badge-${s.source_type}">${s.source_type.toUpperCase()}</span></td>
        <td><strong>${s.article_count}</strong></td>
        <td>${s.search_date || '—'}</td>
        <td>${formatDate(s.imported_at)}</td>
      </tr>`;
    });
    html += '</tbody></table></div>';
    container.innerHTML = html;
  } catch(e) {
    container.innerHTML = '<div class="alert alert-warning"><i class="fa fa-exclamation-triangle"></i>Erro ao carregar fontes.</div>';
  }
})();

loadProjectTeam();
</script>

<?php endif; ?>

<?php
function buildPixPayload(string $pixKey, string $merchantName, string $merchantCity, string $description = '', string $txid = '***'): string
{
  $merchantName = sanitizePixField($merchantName, 25);
  $merchantCity = sanitizePixField($merchantCity, 15);
  $pixKey = preg_replace('/\s+/', '', trim($pixKey));
  $description = sanitizePixField($description, 50);
  $txid = sanitizePixField($txid !== '' ? $txid : '***', 25);

  $merchantAccount = pixField('00', 'BR.GOV.BCB.PIX')
    . pixField('01', $pixKey)
    . ($description !== '' ? pixField('02', $description) : '');

  $payload = ''
    . pixField('00', '01')
    . pixField('26', $merchantAccount)
    . pixField('52', '0000')
    . pixField('53', '986')
    . pixField('58', 'BR')
    . pixField('59', $merchantName)
    . pixField('60', $merchantCity)
    . pixField('62', pixField('05', $txid))
    . '6304';

  return $payload . pixCrc16($payload);
}

function pixField(string $id, string $value): string
{
  return $id . str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT) . $value;
}

function pixCrc16(string $payload): string
{
  $polynomial = 0x1021;
  $result = 0xFFFF;
  $length = strlen($payload);

  for ($offset = 0; $offset < $length; $offset++) {
    $result ^= (ord($payload[$offset]) << 8);
    for ($bitwise = 0; $bitwise < 8; $bitwise++) {
      if (($result <<= 1) & 0x10000) {
        $result ^= $polynomial;
      }
      $result &= 0xFFFF;
    }
  }

  return strtoupper(str_pad(dechex($result), 4, '0', STR_PAD_LEFT));
}

function sanitizePixField(string $value, int $maxLength): string
{
  $value = strtoupper(trim($value));
  $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
  $value = preg_replace('/[^A-Z0-9 \-\.\,\/]/', '', $value);
  $value = preg_replace('/\s+/', ' ', $value);
  return substr($value, 0, $maxLength);
}
?>
