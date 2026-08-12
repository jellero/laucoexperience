<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/../inc/volontariato.php';
require_once __DIR__ . '/_admin_layout.php';

function volunteer_admin_redirect(string $view, string $message, bool $error = false, array $extra = []): never
{
    $_SESSION['volunteer_flash'] = ['message' => $message, 'error' => $error];
    $query = array_merge(['view' => $view], $extra);
    header('Location: volontariato.php?' . http_build_query($query));
    exit;
}

function volunteer_admin_text(string $value, int $max): string
{
    return mb_substr(trim($value), 0, $max);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    try {
        if ($action === 'save_volunteer') {
            $id = (int) ($_POST['id'] ?? 0);
            $status = (string) ($_POST['stato'] ?? 'da_confermare');
            if ($id < 1 || !in_array($status, ['da_confermare','invitato','attivo','in_pausa','ritirato'], true)) throw new RuntimeException('Dati volontario non validi.');
            $pdo->prepare('UPDATE volontari SET stato=:stato,zona=:zona,disponibilita=:disponibilita,note_admin=:note WHERE id=:id')->execute([
                'stato' => $status, 'zona' => volunteer_admin_text((string)($_POST['zona'] ?? ''), 120) ?: null,
                'disponibilita' => volunteer_admin_text((string)($_POST['disponibilita'] ?? ''), 120) ?: null,
                'note' => trim((string)($_POST['note_admin'] ?? '')) ?: null, 'id' => $id,
            ]);
            volunteer_admin_redirect('volontari', 'Volontario aggiornato.');
        }
        if ($action === 'save_group') {
            $id = (int) ($_POST['id'] ?? 0);
            $name = volunteer_admin_text((string)($_POST['nome'] ?? ''), 150);
            $type = (string) ($_POST['tipo'] ?? 'generale');
            if ($name === '' || !in_array($type, ['generale','zona','attivita'], true)) throw new RuntimeException('Nome o tipo del gruppo non valido.');
            $default = !empty($_POST['predefinito']) ? 1 : 0;
            $params = [
                'nome'=>$name,'tipo'=>$type,'zona'=>volunteer_admin_text((string)($_POST['zona'] ?? ''),120) ?: null,
                'descrizione'=>trim((string)($_POST['descrizione'] ?? '')) ?: null,
                'meta'=>volunteer_admin_text((string)($_POST['meta_group_id'] ?? ''),190) ?: null,
                'link'=>volunteer_admin_text((string)($_POST['invite_link'] ?? ''),500) ?: null,
                'predefinito'=>$default,'attivo'=>!empty($_POST['attivo']) ? 1 : 0,
            ];
            $pdo->beginTransaction();
            if ($default) $pdo->exec('UPDATE volontari_gruppi SET predefinito=0');
            if ($id > 0) {
                $params['id']=$id;
                $pdo->prepare('UPDATE volontari_gruppi SET nome=:nome,tipo=:tipo,zona=:zona,descrizione=:descrizione,meta_group_id=:meta,invite_link=:link,predefinito=:predefinito,attivo=:attivo WHERE id=:id')->execute($params);
            } else {
                $pdo->prepare('INSERT INTO volontari_gruppi (nome,tipo,zona,descrizione,meta_group_id,invite_link,predefinito,attivo) VALUES (:nome,:tipo,:zona,:descrizione,:meta,:link,:predefinito,:attivo)')->execute($params);
                $id=(int)$pdo->lastInsertId();
            }
            $pdo->commit();
            volunteer_admin_redirect('gruppi', 'Gruppo salvato.', false, ['group_id'=>$id]);
        }
        if ($action === 'assign_member') {
            $groupId=(int)($_POST['gruppo_id'] ?? 0);$volunteerId=(int)($_POST['volontario_id'] ?? 0);
            if ($groupId<1||$volunteerId<1) throw new RuntimeException('Seleziona gruppo e volontario.');
            $pdo->prepare("INSERT INTO volontari_gruppi_membri (gruppo_id,volontario_id,stato) VALUES (:gruppo,:volontario,'assegnato') ON DUPLICATE KEY UPDATE stato=IF(stato='entrato','entrato','assegnato'),ultimo_errore=NULL")->execute(['gruppo'=>$groupId,'volontario'=>$volunteerId]);
            $outbox=volontariato_queue_invite($pdo,$volunteerId,$groupId);
            $result=volontariato_dispatch_outbox($pdo,$outbox,1);
            volunteer_admin_redirect('gruppi', $result['sent'] ? 'Volontario assegnato e invito inviato.' : 'Volontario assegnato. L’invito è in attesa della configurazione WhatsApp.', false, ['group_id'=>$groupId]);
        }
        if ($action === 'membership_status') {
            $id=(int)($_POST['id']??0);$status=(string)($_POST['stato']??'assegnato');
            if ($id<1||!in_array($status,['assegnato','invito_in_coda','invitato','entrato','uscito','errore'],true)) throw new RuntimeException('Stato membro non valido.');
            $pdo->prepare("UPDATE volontari_gruppi_membri SET stato=:stato,joined_at=IF(:stato2='entrato',COALESCE(joined_at,NOW()),joined_at),left_at=IF(:stato3='uscito',NOW(),NULL) WHERE id=:id")->execute(['stato'=>$status,'stato2'=>$status,'stato3'=>$status,'id'=>$id]);
            volunteer_admin_redirect('gruppi','Stato del partecipante aggiornato.',false,['group_id'=>(int)($_POST['gruppo_id']??0)]);
        }
        if ($action === 'send_group') {
            $groupId=(int)($_POST['gruppo_id']??0);$text=trim((string)($_POST['messaggio']??''));
            $stmt=$pdo->prepare('SELECT meta_group_id FROM volontari_gruppi WHERE id=:id');$stmt->execute(['id'=>$groupId]);$recipient=(string)$stmt->fetchColumn();
            if ($groupId<1||$recipient===''||$text==='') throw new RuntimeException('Configura il Meta Group ID e inserisci il messaggio.');
            $outbox=volontariato_queue_text($pdo,'gruppo',$recipient,$text,null,$groupId);$result=volontariato_dispatch_outbox($pdo,$outbox,1);
            volunteer_admin_redirect('gruppi',$result['sent']?'Messaggio inviato al gruppo.':'Messaggio accodato: verifica l’abilitazione Groups API.',false,['group_id'=>$groupId]);
        }
        if ($action === 'send_direct') {
            $conversationId=(int)($_POST['conversation_id']??0);$text=trim((string)($_POST['messaggio']??''));
            $stmt=$pdo->prepare('SELECT tipo,external_id,volontario_id,gruppo_id FROM whatsapp_conversazioni WHERE id=:id');$stmt->execute(['id'=>$conversationId]);$conversation=$stmt->fetch();
            if (!is_array($conversation)||$text==='') throw new RuntimeException('Conversazione o messaggio non valido.');
            $messageType=(string)$conversation['tipo']==='gruppo'?'gruppo':'diretto';
            $outbox=volontariato_queue_text($pdo,$messageType,(string)$conversation['external_id'],$text,$conversation['volontario_id']?(int)$conversation['volontario_id']:null,$conversation['gruppo_id']?(int)$conversation['gruppo_id']:null);
            $result=volontariato_dispatch_outbox($pdo,$outbox,1);
            volunteer_admin_redirect('chat',$result['sent']?'Risposta inviata.':'Risposta accodata: verifica la configurazione WhatsApp.',false,['conversation_id'=>$conversationId]);
        }
        if ($action === 'mark_read') {
            $id=(int)($_POST['conversation_id']??0);$pdo->prepare('UPDATE whatsapp_conversazioni SET non_letti=0 WHERE id=:id')->execute(['id'=>$id]);
            volunteer_admin_redirect('chat','Conversazione segnata come letta.',false,['conversation_id'=>$id]);
        }
        if ($action === 'save_activity') {
            $id=(int)($_POST['id']??0);$title=volunteer_admin_text((string)($_POST['titolo']??''),190);$status=(string)($_POST['stato']??'bozza');
            if ($title===''||!in_array($status,['bozza','raccolta_adesioni','programmata','in_corso','completata','annullata'],true)) throw new RuntimeException('Titolo o stato attività non valido.');
            $date=trim((string)($_POST['data_ora']??''));$date=$date!==''?str_replace('T',' ',$date).(strlen($date)===16?':00':''):null;
            $params=['gruppo'=>(int)($_POST['gruppo_id']??0)?:null,'percorso'=>(int)($_POST['percorso_id']??0)?:null,'titolo'=>$title,
                'categoria'=>volunteer_admin_text((string)($_POST['categoria']??''),100)?:'Altro','zona'=>volunteer_admin_text((string)($_POST['zona']??''),120)?:null,
                'stato'=>$status,'data'=>$date,'ritrovo'=>volunteer_admin_text((string)($_POST['punto_ritrovo']??''),255)?:null,
                'coordinatore'=>volunteer_admin_text((string)($_POST['coordinatore']??''),150)?:null,'descrizione'=>trim((string)($_POST['descrizione']??''))?:null,
                'sicurezza'=>trim((string)($_POST['note_sicurezza']??''))?:null,'checklist'=>trim((string)($_POST['checklist']??''))?:null,
                'avanzamento'=>max(0,min(100,(int)($_POST['avanzamento']??0))),'chiusura'=>trim((string)($_POST['note_chiusura']??''))?:null];
            if($id>0){$params['id']=$id;$pdo->prepare('UPDATE volontari_attivita SET gruppo_id=:gruppo,percorso_id=:percorso,titolo=:titolo,categoria=:categoria,zona=:zona,stato=:stato,data_ora=:data,punto_ritrovo=:ritrovo,coordinatore=:coordinatore,descrizione=:descrizione,note_sicurezza=:sicurezza,checklist=:checklist,avanzamento=:avanzamento,note_chiusura=:chiusura WHERE id=:id')->execute($params);}else{$params['admin']=admin_id();$pdo->prepare('INSERT INTO volontari_attivita (gruppo_id,percorso_id,titolo,categoria,zona,stato,data_ora,punto_ritrovo,coordinatore,descrizione,note_sicurezza,checklist,avanzamento,note_chiusura,created_by) VALUES (:gruppo,:percorso,:titolo,:categoria,:zona,:stato,:data,:ritrovo,:coordinatore,:descrizione,:sicurezza,:checklist,:avanzamento,:chiusura,:admin)')->execute($params);$id=(int)$pdo->lastInsertId();}
            volunteer_admin_redirect('attivita','Attività salvata.',false,['activity_id'=>$id]);
        }
        if ($action === 'save_trail') {
            $routeId=(int)($_POST['percorso_id']??0);$status=(string)($_POST['stato']??'in_verifica');
            if($routeId<1||!in_array($status,['verificato','attenzione','non_percorribile','in_verifica'],true)) throw new RuntimeException('Stato sentiero non valido.');
            $checked=trim((string)($_POST['ultima_verifica_at']??''));$checked=$checked!==''?str_replace('T',' ',$checked).(strlen($checked)===16?':00':''):null;
            $note=trim((string)($_POST['nota_pubblica']??''))?:null;$next=trim((string)($_POST['prossima_verifica_at']??''))?:null;
            $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO stato_sentieri (percorso_id,stato,nota_pubblica,ultima_verifica_at,prossima_verifica_at,pubblicato,updated_by) VALUES (:percorso,:stato,:nota,:checked,:next,:pubblicato,:admin) ON DUPLICATE KEY UPDATE stato=VALUES(stato),nota_pubblica=VALUES(nota_pubblica),ultima_verifica_at=VALUES(ultima_verifica_at),prossima_verifica_at=VALUES(prossima_verifica_at),pubblicato=VALUES(pubblicato),updated_by=VALUES(updated_by)')->execute(['percorso'=>$routeId,'stato'=>$status,'nota'=>$note,'checked'=>$checked,'next'=>$next,'pubblicato'=>!empty($_POST['pubblicato'])?1:0,'admin'=>admin_id()]);
            if($checked!==null)$pdo->prepare('INSERT INTO stato_sentieri_verifiche (percorso_id,stato,nota,verificato_at,created_by) VALUES (:percorso,:stato,:nota,:checked,:admin)')->execute(['percorso'=>$routeId,'stato'=>$status,'nota'=>$note,'checked'=>$checked,'admin'=>admin_id()]);
            $pdo->commit();volunteer_admin_redirect('sentieri','Stato del sentiero aggiornato.');
        }
        if ($action === 'process_queue') {
            $result=volontariato_dispatch_outbox($pdo,null,50);volunteer_admin_redirect('overview',"Coda elaborata: {$result['sent']} inviati, {$result['failed']} errori.",$result['failed']>0);
        }
        throw new RuntimeException('Operazione non riconosciuta.');
    } catch (Throwable $error) {
        if($pdo->inTransaction())$pdo->rollBack();
        volunteer_admin_redirect((string)($_POST['return_view']??'overview'),$error->getMessage(),true);
    }
}

$view=(string)($_GET['view']??'overview');
if(!in_array($view,['overview','volontari','gruppi','chat','attivita','sentieri'],true))$view='overview';
$flash=$_SESSION['volunteer_flash']??null;unset($_SESSION['volunteer_flash']);
$moduleReady=true;$moduleError='';
try{$pdo->query('SELECT 1 FROM volontari LIMIT 1');}catch(Throwable){$moduleReady=false;$moduleError='Le tabelle del modulo non sono ancora presenti: applica la migrazione 20260812_volontariato_whatsapp.sql.';}
$summary=['volontari'=>0,'attivi'=>0,'gruppi'=>0,'non_letti'=>0,'attivita'=>0,'sentieri_critici'=>0];
$groups=$volunteers=$conversations=$activities=$routes=$queue=[];
if($moduleReady){
    foreach(['volontari'=>'SELECT COUNT(*) FROM volontari','attivi'=>"SELECT COUNT(*) FROM volontari WHERE stato='attivo'",'gruppi'=>'SELECT COUNT(*) FROM volontari_gruppi WHERE attivo=1','non_letti'=>'SELECT COALESCE(SUM(non_letti),0) FROM whatsapp_conversazioni','attivita'=>"SELECT COUNT(*) FROM volontari_attivita WHERE stato NOT IN ('completata','annullata')",'sentieri_critici'=>"SELECT COUNT(*) FROM stato_sentieri WHERE pubblicato=1 AND stato IN ('attenzione','non_percorribile')"] as $key=>$sql){try{$summary[$key]=(int)$pdo->query($sql)->fetchColumn();}catch(Throwable){}}
    $groups=$pdo->query('SELECT g.*,(SELECT COUNT(*) FROM volontari_gruppi_membri m WHERE m.gruppo_id=g.id AND m.stato<>\'uscito\') AS membri FROM volontari_gruppi g ORDER BY g.attivo DESC,g.predefinito DESC,g.nome')->fetchAll();
    $volunteers=$pdo->query("SELECT v.*,(SELECT GROUP_CONCAT(g.nome ORDER BY g.nome SEPARATOR ', ') FROM volontari_gruppi_membri m JOIN volontari_gruppi g ON g.id=m.gruppo_id WHERE m.volontario_id=v.id AND m.stato<>'uscito') AS gruppi FROM volontari v ORDER BY FIELD(v.stato,'da_confermare','invitato','attivo','in_pausa','ritirato'),v.created_at DESC")->fetchAll();
    $conversations=$pdo->query('SELECT c.*,v.nome AS volontario_nome,g.nome AS gruppo_nome FROM whatsapp_conversazioni c LEFT JOIN volontari v ON v.id=c.volontario_id LEFT JOIN volontari_gruppi g ON g.id=c.gruppo_id ORDER BY c.ultimo_messaggio_at DESC,c.id DESC')->fetchAll();
    $activities=$pdo->query('SELECT a.*,g.nome AS gruppo_nome,p.titolo AS percorso_titolo FROM volontari_attivita a LEFT JOIN volontari_gruppi g ON g.id=a.gruppo_id LEFT JOIN percorsi p ON p.id=a.percorso_id ORDER BY FIELD(a.stato,\'in_corso\',\'programmata\',\'raccolta_adesioni\',\'bozza\',\'completata\',\'annullata\'),a.data_ora')->fetchAll();
    $routes=$pdo->query('SELECT p.id,p.titolo,p.tipo,s.stato,s.nota_pubblica,s.ultima_verifica_at,s.prossima_verifica_at,COALESCE(s.pubblicato,1) AS stato_pubblicato FROM percorsi p LEFT JOIN stato_sentieri s ON s.percorso_id=p.id WHERE p.pubblicato=1 ORDER BY p.tipo,p.ordine,p.titolo')->fetchAll();
    $queue=$pdo->query('SELECT o.*,v.nome AS volontario_nome,g.nome AS gruppo_nome FROM whatsapp_outbox o LEFT JOIN volontari v ON v.id=o.volontario_id LEFT JOIN volontari_gruppi g ON g.id=o.gruppo_id ORDER BY o.id DESC LIMIT 20')->fetchAll();
}
$selectedGroup=null;$members=[];if($moduleReady&&(int)($_GET['group_id']??0)>0){$stmt=$pdo->prepare('SELECT * FROM volontari_gruppi WHERE id=:id');$stmt->execute(['id'=>(int)$_GET['group_id']]);$selectedGroup=$stmt->fetch()?:null;if($selectedGroup){$stmt=$pdo->prepare('SELECT m.*,v.nome,v.telefono FROM volontari_gruppi_membri m JOIN volontari v ON v.id=m.volontario_id WHERE m.gruppo_id=:id ORDER BY v.nome');$stmt->execute(['id'=>(int)$selectedGroup['id']]);$members=$stmt->fetchAll();}}
$selectedConversation=null;$messages=[];if($moduleReady&&(int)($_GET['conversation_id']??0)>0){$stmt=$pdo->prepare('SELECT c.*,v.nome AS volontario_nome,g.nome AS gruppo_nome FROM whatsapp_conversazioni c LEFT JOIN volontari v ON v.id=c.volontario_id LEFT JOIN volontari_gruppi g ON g.id=c.gruppo_id WHERE c.id=:id');$stmt->execute(['id'=>(int)$_GET['conversation_id']]);$selectedConversation=$stmt->fetch()?:null;if($selectedConversation){$stmt=$pdo->prepare('SELECT * FROM whatsapp_messaggi WHERE conversazione_id=:id ORDER BY messaggio_at,id');$stmt->execute(['id'=>(int)$selectedConversation['id']]);$messages=$stmt->fetchAll();}}
$selectedActivity=null;if($moduleReady&&(int)($_GET['activity_id']??0)>0){$stmt=$pdo->prepare('SELECT * FROM volontari_attivita WHERE id=:id');$stmt->execute(['id'=>(int)$_GET['activity_id']]);$selectedActivity=$stmt->fetch()?:null;}
$allRoutes=$moduleReady?$pdo->query('SELECT id,titolo FROM percorsi WHERE pubblicato=1 ORDER BY titolo')->fetchAll():[];

admin_page_open('Volontariato e territorio','volontariato');
?>
<style>
.vol-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:22px}.vol-tabs a{padding:11px 14px;background:#fff;text-decoration:none;border:1px solid #ddd;font-weight:700;font-size:13px}.vol-tabs a.active{background:#222;color:#fff}.vol-summary{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-bottom:22px}.vol-stat{background:#fff;box-shadow:var(--admin-shadow);padding:22px}.vol-stat strong{display:block;font-size:32px;margin-top:6px}.vol-two{display:grid;grid-template-columns:1fr 1fr;gap:22px;align-items:start}.vol-stack{display:grid;gap:16px}.vol-card{background:#fff;box-shadow:var(--admin-shadow);padding:22px}.vol-card h2,.vol-card h3{margin-top:0}.vol-badge{display:inline-block;padding:5px 8px;background:#eee;font-size:12px;font-weight:700}.vol-badge.attivo,.vol-badge.entrato,.vol-badge.inviato,.vol-badge.verificato{background:#d1e7dd;color:#0f5132}.vol-badge.errore,.vol-badge.fallito,.vol-badge.non_percorribile{background:#f8d7da;color:#842029}.vol-badge.attenzione,.vol-badge.configurazione_mancante,.vol-badge.in_coda{background:#fff3cd;color:#664d03}.vol-readiness{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}.vol-ready{padding:14px;background:#f8d7da;color:#842029}.vol-ready.ok{background:#d1e7dd;color:#0f5132}.vol-chat{display:grid;grid-template-columns:330px minmax(0,1fr);gap:20px}.vol-conversations{background:#fff;max-height:680px;overflow:auto}.vol-conversation{display:block;padding:15px;border-bottom:1px solid #eee;text-decoration:none}.vol-conversation.active{background:#222;color:#fff}.vol-message-list{display:flex;flex-direction:column;gap:10px;max-height:500px;overflow:auto;padding:15px;background:#f4f4f4}.vol-message{max-width:80%;padding:11px 13px;background:#fff;align-self:flex-start;white-space:pre-wrap}.vol-message.uscita{background:#d7f0df;align-self:flex-end}.vol-progress{height:8px;background:#eee;margin-top:9px}.vol-progress span{display:block;height:100%;background:#222}.vol-table-wrap{overflow:auto}.vol-trail-form{min-width:830px;display:grid;grid-template-columns:1.2fr 170px 1fr 180px 150px auto;gap:8px;align-items:end;padding:12px 0;border-bottom:1px solid #eee}.vol-trail-form textarea{min-height:70px}.vol-trail-form label{margin:0}.vol-inline-actions{display:flex;gap:8px;flex-wrap:wrap}.vol-empty{color:#777}.vol-consent{font-size:12px;color:#666;margin-top:8px}@media(max-width:900px){.vol-summary{grid-template-columns:1fr 1fr}.vol-two,.vol-chat{grid-template-columns:1fr}.vol-readiness{grid-template-columns:1fr}.vol-conversations{max-height:300px}}@media(max-width:600px){.vol-summary{grid-template-columns:1fr}}
</style>
<main class="wrap">
<section class="hero-admin"><h1>Volontariato e cura del territorio</h1><p>Un solo pannello per disponibilità, gruppi WhatsApp, conversazioni, attività e stato dei sentieri.</p></section>
<?php if(is_array($flash)):?><div class="<?=!empty($flash['error'])?'error':'success'?>"><?=e($flash['message']??'')?></div><?php endif;?>
<?php if(!$moduleReady):?><div class="error"><?=e($moduleError)?></div></main><?php admin_page_close();return;endif;?>
<nav class="vol-tabs" aria-label="Sezioni volontariato">
<?php foreach(['overview'=>'Riepilogo','volontari'=>'Volontari','gruppi'=>'Gruppi','chat'=>'Chat WhatsApp','attivita'=>'Planning','sentieri'=>'Stato sentieri'] as $key=>$label):?><a class="<?=$view===$key?'active':''?>" href="?view=<?=e($key)?>"><?=e($label)?><?php if($key==='chat'&&$summary['non_letti']>0):?> (<?=$summary['non_letti']?>)<?php endif;?></a><?php endforeach;?>
</nav>

<?php if($view==='overview'):?>
<section class="vol-summary"><div class="vol-stat">Volontari<strong><?=$summary['volontari']?></strong><small><?=$summary['attivi']?> attivi</small></div><div class="vol-stat">Gruppi attivi<strong><?=$summary['gruppi']?></strong></div><div class="vol-stat">Chat da leggere<strong><?=$summary['non_letti']?></strong></div><div class="vol-stat">Attività aperte<strong><?=$summary['attivita']?></strong></div><div class="vol-stat">Sentieri con criticità<strong><?=$summary['sentieri_critici']?></strong></div></section>
<div class="vol-two"><section class="vol-card"><h2>Configurazione WhatsApp</h2><div class="vol-readiness"><div class="vol-ready <?=volontariato_whatsapp_ready()?'ok':''?>"><strong>Cloud API</strong><br><?=volontariato_whatsapp_ready()?'Pronta':'Da configurare'?></div><div class="vol-ready <?=trim((string)lauco_env('WHATSAPP_INVITE_TEMPLATE_NAME',''))!==''?'ok':''?>"><strong>Template invito</strong><br><?=trim((string)lauco_env('WHATSAPP_INVITE_TEMPLATE_NAME',''))!==''?'Configurato':'Manca il nome'?></div><div class="vol-ready <?=volontariato_whatsapp_ready(true)?'ok':''?>"><strong>Groups API</strong><br><?=volontariato_whatsapp_ready(true)?'Abilitata':'Non abilitata'?></div></div><p class="hint">Il modulo salva sempre le iscrizioni. Inviti, chat e messaggi ai gruppi partono quando le credenziali Meta sono complete. Webhook: <code><?=e(rtrim((string)lauco_env('APP_URL','https://laucoexperience.it'),'/'))?>/api/whatsapp/webhook</code>.</p><form method="post"><input type="hidden" name="_csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="process_queue"><button class="btn" type="submit">Elabora coda adesso</button></form></section><section class="vol-card"><h2>Ultimi invii</h2><?php if(!$queue):?><p class="vol-empty">Nessun invio.</p><?php else:?><div class="vol-stack"><?php foreach(array_slice($queue,0,8) as $item):?><div><span class="vol-badge <?=e($item['stato'])?>"><?=e(volontariato_status_label((string)$item['stato']))?></span> <?=e($item['volontario_nome']?:$item['gruppo_nome']?:$item['destinatario'])?><br><small><?=e($item['ultimo_errore']??$item['created_at'])?></small></div><?php endforeach;?></div><?php endif;?></section></div>

<?php elseif($view==='volontari'):?>
<section class="vol-stack"><?php if(!$volunteers):?><div class="vol-card"><p>Nessuna disponibilità registrata.</p></div><?php endif;?><?php foreach($volunteers as $volunteer):?><details class="vol-card"><summary><strong><?=e($volunteer['nome'])?></strong> · <?=e($volunteer['telefono'])?> · <span class="vol-badge <?=e($volunteer['stato'])?>"><?=e(volontariato_status_label((string)$volunteer['stato']))?></span></summary><div class="vol-two" style="margin-top:18px"><div><p><strong>Email:</strong> <?=e($volunteer['email']?:'-')?><br><strong>Zona:</strong> <?=e($volunteer['zona']?:'-')?><br><strong>Disponibilità:</strong> <?=e($volunteer['disponibilita']?:'-')?><br><strong>Gruppi:</strong> <?=e($volunteer['gruppi']?:'-')?></p><p><strong>Interessi:</strong> <?php $labels=[];foreach(volontariato_decode_interessi($volunteer['interessi_json']) as $i)$labels[]=volontariato_interessi()[$i]??$i;echo e(implode(', ',$labels));?></p><p class="vol-consent">Privacy, WhatsApp, visibilità nel gruppo e maggiore età confermati il <?=e(date('d/m/Y H:i',strtotime((string)$volunteer['consenso_at'])))?>.</p></div><form method="post" class="grid"><input type="hidden" name="_csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="save_volunteer"><input type="hidden" name="id" value="<?=(int)$volunteer['id']?>"><label>Stato<select name="stato"><?php foreach(['da_confermare','invitato','attivo','in_pausa','ritirato'] as $s):?><option value="<?=$s?>" <?=$volunteer['stato']===$s?'selected':''?>><?=e(volontariato_status_label($s))?></option><?php endforeach;?></select></label><label>Zona<input name="zona" value="<?=e($volunteer['zona'])?>"></label><label>Disponibilità<input name="disponibilita" value="<?=e($volunteer['disponibilita'])?>"></label><label class="full">Note interne<textarea name="note_admin"><?=e($volunteer['note_admin'])?></textarea></label><button class="btn" type="submit">Salva volontario</button></form></div></details><?php endforeach;?></section>

<?php elseif($view==='gruppi'):?>
<div class="vol-two"><section class="vol-card"><h2>Gruppi</h2><div class="vol-stack"><?php foreach($groups as $group):?><a href="?view=gruppi&group_id=<?=(int)$group['id']?>" style="text-decoration:none"><strong><?=e($group['nome'])?></strong> <span class="vol-badge"><?=e($group['tipo'])?></span><br><small><?=(int)$group['membri']?> partecipanti<?=empty($group['meta_group_id'])?' · Meta ID mancante':''?></small></a><?php endforeach;?></div><hr><a class="btn" href="?view=gruppi">Nuovo gruppo</a></section><section class="vol-card"><h2><?=$selectedGroup?'Modifica gruppo':'Nuovo gruppo'?></h2><form method="post" class="grid"><input type="hidden" name="_csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="save_group"><input type="hidden" name="id" value="<?=(int)($selectedGroup['id']??0)?>"><label>Nome<input name="nome" required value="<?=e($selectedGroup['nome']??'')?>"></label><label>Tipo<select name="tipo"><?php foreach(['generale','zona','attivita'] as $type):?><option value="<?=$type?>" <?=($selectedGroup['tipo']??'')===$type?'selected':''?>><?=e(ucfirst($type))?></option><?php endforeach;?></select></label><label>Zona<input name="zona" value="<?=e($selectedGroup['zona']??'')?>"></label><label>Meta Group ID<input name="meta_group_id" value="<?=e($selectedGroup['meta_group_id']??'')?>"></label><label class="full">Link di invito WhatsApp<input type="url" name="invite_link" value="<?=e($selectedGroup['invite_link']??'')?>"></label><label class="full">Descrizione<textarea name="descrizione"><?=e($selectedGroup['descrizione']??'')?></textarea></label><label><input type="checkbox" name="predefinito" value="1" <?=!empty($selectedGroup['predefinito'])?'checked':''?>> Gruppo predefinito</label><label><input type="checkbox" name="attivo" value="1" <?=!$selectedGroup||!empty($selectedGroup['attivo'])?'checked':''?>> Attivo</label><button class="btn" type="submit">Salva gruppo</button></form></section></div>
<?php if($selectedGroup):?><div class="vol-two" style="margin-top:22px"><section class="vol-card"><h2>Partecipanti</h2><form method="post" class="grid"><input type="hidden" name="_csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="assign_member"><input type="hidden" name="gruppo_id" value="<?=(int)$selectedGroup['id']?>"><label class="full">Aggiungi e invia invito<select name="volontario_id" required><option value="">Seleziona volontario</option><?php foreach($volunteers as $v):?><option value="<?=(int)$v['id']?>"><?=e($v['nome'].' · '.$v['telefono'])?></option><?php endforeach;?></select></label><button class="btn" type="submit">Assegna e invita</button></form><div class="vol-stack" style="margin-top:20px"><?php foreach($members as $member):?><form method="post" class="vol-inline-actions"><input type="hidden" name="_csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="membership_status"><input type="hidden" name="id" value="<?=(int)$member['id']?>"><input type="hidden" name="gruppo_id" value="<?=(int)$selectedGroup['id']?>"><strong><?=e($member['nome'])?></strong><select name="stato" style="width:auto"><?php foreach(['assegnato','invito_in_coda','invitato','entrato','uscito','errore'] as $s):?><option value="<?=$s?>" <?=$member['stato']===$s?'selected':''?>><?=e(volontariato_status_label($s))?></option><?php endforeach;?></select><button class="mini-btn" type="submit">Aggiorna</button></form><?php endforeach;?></div></section><section class="vol-card"><h2>Invia al gruppo</h2><p class="hint">Richiede l’abilitazione ufficiale WhatsApp Groups API e il Meta Group ID.</p><form method="post"><input type="hidden" name="_csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="send_group"><input type="hidden" name="gruppo_id" value="<?=(int)$selectedGroup['id']?>"><label>Messaggio<textarea name="messaggio" required></textarea></label><button class="btn" type="submit">Invia al gruppo</button></form></section></div><?php endif;?>

<?php elseif($view==='chat'):?>
<div class="vol-chat"><aside class="vol-conversations"><?php if(!$conversations):?><p style="padding:18px">Nessuna conversazione ricevuta.</p><?php endif;?><?php foreach($conversations as $conversation):?><a class="vol-conversation <?=($selectedConversation['id']??0)===$conversation['id']?'active':''?>" href="?view=chat&conversation_id=<?=(int)$conversation['id']?>"><strong><?=e($conversation['volontario_nome']?:$conversation['gruppo_nome']?:$conversation['titolo']?:$conversation['external_id'])?></strong><?php if($conversation['non_letti']):?> <span class="vol-badge"><?=(int)$conversation['non_letti']?></span><?php endif;?><br><small><?=e($conversation['ultimo_messaggio_at']?:'')?></small></a><?php endforeach;?></aside><section class="vol-card"><?php if(!$selectedConversation):?><p>Seleziona una conversazione.</p><?php else:?><h2><?=e($selectedConversation['volontario_nome']?:$selectedConversation['gruppo_nome']?:$selectedConversation['external_id'])?></h2><div class="vol-message-list"><?php foreach($messages as $message):?><div class="vol-message <?=e($message['direzione'])?>"><?=e($message['testo']?:'['.$message['tipo'].']')?><br><small><?=e(date('d/m/Y H:i',strtotime((string)$message['messaggio_at'])))?> · <?=e($message['stato'])?></small></div><?php endforeach;?></div><div class="vol-inline-actions" style="margin-top:16px"><form method="post" style="flex:1"><input type="hidden" name="_csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="send_direct"><input type="hidden" name="conversation_id" value="<?=(int)$selectedConversation['id']?>"><label>Rispondi<textarea name="messaggio" required></textarea></label><button class="btn" type="submit">Invia risposta</button></form><form method="post"><input type="hidden" name="_csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="mark_read"><input type="hidden" name="conversation_id" value="<?=(int)$selectedConversation['id']?>"><button class="btn secondary" type="submit">Segna letta</button></form></div><?php endif;?></section></div>

<?php elseif($view==='attivita'):?>
<div class="vol-two"><section class="vol-card"><h2><?=$selectedActivity?'Modifica attività':'Nuova attività'?></h2><form method="post" class="grid"><input type="hidden" name="_csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="save_activity"><input type="hidden" name="id" value="<?=(int)($selectedActivity['id']??0)?>"><label class="full">Titolo<input name="titolo" required value="<?=e($selectedActivity['titolo']??'')?>"></label><label>Categoria<input name="categoria" value="<?=e($selectedActivity['categoria']??'')?>" placeholder="Pulizia, controllo, evento…"></label><label>Stato<select name="stato"><?php foreach(['bozza','raccolta_adesioni','programmata','in_corso','completata','annullata'] as $s):?><option value="<?=$s?>" <?=($selectedActivity['stato']??'bozza')===$s?'selected':''?>><?=e(volontariato_status_label($s))?></option><?php endforeach;?></select></label><label>Gruppo<select name="gruppo_id"><option value="">Nessuno</option><?php foreach($groups as $g):?><option value="<?=(int)$g['id']?>" <?=($selectedActivity['gruppo_id']??0)==$g['id']?'selected':''?>><?=e($g['nome'])?></option><?php endforeach;?></select></label><label>Itinerario<select name="percorso_id"><option value="">Nessuno</option><?php foreach($allRoutes as $r):?><option value="<?=(int)$r['id']?>" <?=($selectedActivity['percorso_id']??0)==$r['id']?'selected':''?>><?=e($r['titolo'])?></option><?php endforeach;?></select></label><label>Data e ora<input type="datetime-local" name="data_ora" value="<?=!empty($selectedActivity['data_ora'])?e(date('Y-m-d\TH:i',strtotime((string)$selectedActivity['data_ora']))):''?>"></label><label>Zona<input name="zona" value="<?=e($selectedActivity['zona']??'')?>"></label><label>Punto di ritrovo<input name="punto_ritrovo" value="<?=e($selectedActivity['punto_ritrovo']??'')?>"></label><label>Coordinatore<input name="coordinatore" value="<?=e($selectedActivity['coordinatore']??'')?>"></label><label class="full">Descrizione<textarea name="descrizione"><?=e($selectedActivity['descrizione']??'')?></textarea></label><label class="full">Sicurezza e materiali<textarea name="note_sicurezza"><?=e($selectedActivity['note_sicurezza']??'')?></textarea></label><label class="full">Checklist<textarea name="checklist"><?=e($selectedActivity['checklist']??'')?></textarea></label><label>Avanzamento %<input type="number" min="0" max="100" name="avanzamento" value="<?=(int)($selectedActivity['avanzamento']??0)?>"></label><label class="full">Note di chiusura<textarea name="note_chiusura"><?=e($selectedActivity['note_chiusura']??'')?></textarea></label><button class="btn" type="submit">Salva attività</button></form></section><section class="vol-card"><h2>Planning</h2><div class="vol-stack"><?php foreach($activities as $activity):?><a href="?view=attivita&activity_id=<?=(int)$activity['id']?>" style="text-decoration:none"><span class="vol-badge <?=e($activity['stato'])?>"><?=e(volontariato_status_label((string)$activity['stato']))?></span><strong style="display:block;margin-top:6px"><?=e($activity['titolo'])?></strong><small><?=e($activity['data_ora']?date('d/m/Y H:i',strtotime((string)$activity['data_ora'])):'Data da definire')?><?=!empty($activity['gruppo_nome'])?' · '.e($activity['gruppo_nome']):''?></small><div class="vol-progress"><span style="width:<?=(int)$activity['avanzamento']?>%"></span></div></a><?php endforeach;?></div></section></div>

<?php elseif($view==='sentieri'):?>
<section class="vol-card"><h2>Stato pubblico dei sentieri</h2><p class="hint">Ogni salvataggio con una data di verifica crea anche una voce nello storico. Le segnalazioni esistenti restano separate.</p><div class="vol-table-wrap"><?php foreach($routes as $route):?><form method="post" class="vol-trail-form"><input type="hidden" name="_csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="save_trail"><input type="hidden" name="percorso_id" value="<?=(int)$route['id']?>"><label>Itinerario<strong style="display:block"><?=e($route['titolo'])?></strong><small><?=e(strtoupper((string)$route['tipo']))?></small></label><label>Stato<select name="stato"><?php foreach(['verificato','attenzione','non_percorribile','in_verifica'] as $s):?><option value="<?=$s?>" <?=($route['stato']??'in_verifica')===$s?'selected':''?>><?=e(volontariato_status_label($s))?></option><?php endforeach;?></select></label><label>Nota pubblica<textarea name="nota_pubblica"><?=e($route['nota_pubblica']??'')?></textarea></label><label>Ultima verifica<input type="datetime-local" name="ultima_verifica_at" value="<?=!empty($route['ultima_verifica_at'])?e(date('Y-m-d\TH:i',strtotime((string)$route['ultima_verifica_at']))):''?>"></label><label>Prossimo controllo<input type="date" name="prossima_verifica_at" value="<?=e($route['prossima_verifica_at']??'')?>"><span><input type="checkbox" name="pubblicato" value="1" <?=!empty($route['stato_pubblicato'])?'checked':''?>> Pubblico</span></label><button class="mini-btn" type="submit">Salva</button></form><?php endforeach;?></div></section>
<?php endif;?>
</main>
<?php admin_page_close(); ?>
