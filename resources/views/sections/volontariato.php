<?php
require_once LAUCO_ROOT . '/inc/volontariato.php';
$volunteerInterestKeys = [
    'sentieri' => 'volunteer.interest.trails', 'pulizia' => 'volunteer.interest.cleaning',
    'segnaletica' => 'volunteer.interest.signage', 'foto' => 'volunteer.interest.photos',
    'memoria' => 'volunteer.interest.memory', 'eventi' => 'volunteer.interest.events',
];
?>
<section id="call-to-action-partecipa" class="elementor-section elementor-top-section elementor-section-full_width elementor-section-height-default">
    <div class="row text margin-leftright-null color-background cta-partecipa">
        <div class="container">
            <div class="row margin-leftright-null cta-volunteer-grid">
                <div class="col-md-6 col-sm-12 cta-volunteer-copy">
                    <span class="cta-kicker"><?= e(site_text('volunteer.kicker', null, 'Volontariato e cura del territorio')) ?></span>
                    <h4 class="big white margin-bottom-small"><?= e(site_text('volunteer.title', null, 'Vuoi dare una mano concreta?')) ?></h4>
                    <p class="white cta-partecipa-text"><?= e(site_text('volunteer.intro', null, 'Scegli come contribuire: anche poche ore, quando puoi, possono essere preziose per Lauco.')) ?></p>
                    <ul class="cta-volunteer-activities">
                        <?php foreach ($volunteerInterestKeys as $interestKey => $translationKey): ?>
                            <li><?= e(site_text($translationKey, null, volontariato_interessi()[$interestKey])) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="white cta-partecipa-subtext"><?= e(site_text('volunteer.no_commitment', null, 'Non è richiesto un impegno continuativo. Ti avviseremo delle attività e sarai sempre tu a scegliere se partecipare.')) ?></p>
                    <a class="cta-trail-status-link" href="/stato-sentieri"><?= e(site_text('volunteer.trail_status_link', null, 'Consulta lo stato dei sentieri')) ?></a>
                </div>

                <div class="col-md-6 col-sm-12 cta-partecipa-actions">
                    <form id="volunteerSignupForm" class="cta-partecipa-box" action="/volontariato/iscrizione" method="post" novalidate>
                        <h5 class="white"><?= e(site_text('volunteer.form.title', null, 'Dai la tua disponibilità')) ?></h5>
                        <p class="white volunteer-form-intro"><?= e(site_text('volunteer.form.intro', null, 'Compila il modulo: riceverai automaticamente su WhatsApp il link per entrare nel gruppo operativo.')) ?></p>
                        <div class="volunteer-hp" aria-hidden="true"><input type="text" name="company_website" tabindex="-1" autocomplete="off"></div>
                        <div class="volunteer-fields">
                            <label><span><?= e(site_text('volunteer.form.name', null, 'Nome e cognome')) ?> *</span><input type="text" name="nome" maxlength="150" autocomplete="name" required></label>
                            <label><span><?= e(site_text('volunteer.form.phone', null, 'Numero WhatsApp')) ?> *</span><input type="tel" name="telefono" maxlength="24" autocomplete="tel" placeholder="+39 333 1234567" required></label>
                            <label><span><?= e(site_text('volunteer.form.email', null, 'Email (facoltativa)')) ?></span><input type="email" name="email" maxlength="190" autocomplete="email"></label>
                            <label><span><?= e(site_text('volunteer.form.zone', null, 'Zona o frazione')) ?></span><select name="zona"><option value=""><?= e(site_text('volunteer.form.choose', null, 'Seleziona')) ?></option><?php foreach (volontariato_zone() as $zone): ?><option value="<?= e($zone) ?>"><?= e($zone) ?></option><?php endforeach; ?></select></label>
                            <label><span><?= e(site_text('volunteer.form.availability', null, 'Disponibilità')) ?></span><select name="disponibilita"><option value=""><?= e(site_text('volunteer.form.choose', null, 'Seleziona')) ?></option><?php foreach (volontariato_disponibilita() as $availability): ?><option value="<?= e($availability) ?>"><?= e($availability) ?></option><?php endforeach; ?></select></label>
                        </div>
                        <fieldset class="volunteer-interests">
                            <legend><?= e(site_text('volunteer.form.activities', null, 'Come vorresti contribuire?')) ?> *</legend>
                            <?php foreach ($volunteerInterestKeys as $interestKey => $translationKey): ?>
                                <label><input type="checkbox" name="interessi[]" value="<?= e($interestKey) ?>"><span><?= e(site_text($translationKey, null, volontariato_interessi()[$interestKey])) ?></span></label>
                            <?php endforeach; ?>
                        </fieldset>
                        <div class="volunteer-consents">
                            <label><input type="checkbox" name="maggiorenne" value="1" required><span><?= e(site_text('volunteer.consent.adult', null, 'Confermo di avere almeno 18 anni.')) ?> *</span></label>
                            <label><input type="checkbox" name="consenso_whatsapp" value="1" required><span><?= e(site_text('volunteer.consent.whatsapp', null, 'Acconsento a ricevere comunicazioni operative su WhatsApp.')) ?> *</span></label>
                            <label><input type="checkbox" name="consenso_gruppo" value="1" required><span><?= e(site_text('volunteer.consent.group', null, 'So che, entrando nel gruppo, il mio numero sarà visibile agli altri partecipanti.')) ?> *</span></label>
                            <label><input type="checkbox" name="consenso_privacy" value="1" required><span><?= site_text('volunteer.consent.privacy', null, 'Ho letto la <a href="/privacy">privacy policy</a> e acconsento al trattamento dei dati.') ?> *</span></label>
                        </div>
                        <button class="btn-pro simple white volunteer-submit" type="submit"><?= e(site_text('volunteer.form.submit', null, 'Dai la tua disponibilità')) ?></button>
                        <div id="volunteerSignupMessage" class="volunteer-form-message" role="status" aria-live="polite"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    #call-to-action-partecipa .cta-partecipa{padding:70px 0}
    #call-to-action-partecipa .cta-volunteer-grid{display:flex;align-items:flex-start}
    #call-to-action-partecipa .cta-volunteer-copy{padding-right:48px}
    #call-to-action-partecipa .cta-kicker{display:block;color:#fff;text-transform:uppercase;letter-spacing:.12em;font-size:12px;font-weight:700;margin-bottom:12px;opacity:.8}
    #call-to-action-partecipa h4{line-height:1.25}
    #call-to-action-partecipa .cta-partecipa-text{font-size:18px;line-height:1.7;opacity:.96}
    #call-to-action-partecipa .cta-partecipa-subtext{font-size:15px;line-height:1.7;opacity:.86;margin-top:22px}
    #call-to-action-partecipa .cta-volunteer-activities{display:grid;grid-template-columns:1fr 1fr;gap:10px 20px;margin:24px 0;padding:0;list-style:none;color:#fff}
    #call-to-action-partecipa .cta-volunteer-activities li{position:relative;padding-left:24px;line-height:1.45}
    #call-to-action-partecipa .cta-volunteer-activities li:before{content:'✓';position:absolute;left:0;font-weight:700}
    #call-to-action-partecipa .cta-trail-status-link{display:inline-block;color:#fff;border-bottom:1px solid rgba(255,255,255,.65);padding-bottom:3px;margin-top:8px}
    #call-to-action-partecipa .cta-partecipa-box{width:100%;padding:30px;border:1px solid rgba(255,255,255,.28);background:rgba(255,255,255,.1);text-align:left}
    #call-to-action-partecipa .cta-partecipa-box,#call-to-action-partecipa .cta-partecipa-box *{box-sizing:border-box}
    #call-to-action-partecipa .cta-partecipa-box h5{margin:0 0 8px;font-size:22px;font-weight:700}
    #call-to-action-partecipa .volunteer-form-intro{font-size:14px;line-height:1.55;margin-bottom:22px;opacity:.92}
    #call-to-action-partecipa .volunteer-fields{display:grid;grid-template-columns:1fr 1fr;gap:14px}
    #call-to-action-partecipa label span,#call-to-action-partecipa legend{color:#fff;font-size:13px;line-height:1.4}
    #call-to-action-partecipa label>span{display:block;margin-bottom:6px}
    #call-to-action-partecipa input,#call-to-action-partecipa select{width:100%;height:44px;border:1px solid rgba(255,255,255,.42);background:#fff;color:#222;padding:9px 10px}
    #call-to-action-partecipa fieldset{border:0;padding:0;margin:20px 0}
    #call-to-action-partecipa legend{font-weight:700;margin-bottom:10px}
    #call-to-action-partecipa .volunteer-interests{display:grid;grid-template-columns:1fr 1fr;gap:8px 15px}
    #call-to-action-partecipa .volunteer-interests legend{grid-column:1/-1}
    #call-to-action-partecipa .volunteer-interests label,#call-to-action-partecipa .volunteer-consents label{display:flex;align-items:flex-start;gap:8px;cursor:pointer}
    #call-to-action-partecipa .volunteer-interests input,#call-to-action-partecipa .volunteer-consents input{width:17px;height:17px;flex:0 0 17px;margin:2px 0 0}
    #call-to-action-partecipa .volunteer-interests label span,#call-to-action-partecipa .volunteer-consents label span{margin:0}
    #call-to-action-partecipa .volunteer-consents{display:grid;gap:8px;margin-bottom:20px}
    #call-to-action-partecipa .volunteer-consents a{color:#fff;text-decoration:underline}
    #call-to-action-partecipa .volunteer-submit{width:100%;border:1px solid #fff;cursor:pointer}
    #call-to-action-partecipa .volunteer-submit[disabled]{opacity:.55;cursor:wait}
    #call-to-action-partecipa .volunteer-form-message{display:none;margin-top:14px;padding:12px;background:#fff;color:#222;line-height:1.45}
    #call-to-action-partecipa .volunteer-form-message.is-visible{display:block}
    #call-to-action-partecipa .volunteer-form-message.is-error{background:#ffe5e5;color:#730000}
    #call-to-action-partecipa .volunteer-hp{position:absolute!important;left:-9999px!important;width:1px;height:1px;overflow:hidden}
    @media(max-width:991px){#call-to-action-partecipa .cta-partecipa{padding:55px 0}#call-to-action-partecipa .cta-volunteer-grid{display:block}#call-to-action-partecipa .cta-volunteer-copy{padding-right:15px;margin-bottom:32px}}
    @media(max-width:600px){#call-to-action-partecipa .cta-partecipa{padding:44px 0}#call-to-action-partecipa .cta-volunteer-activities,#call-to-action-partecipa .volunteer-fields,#call-to-action-partecipa .volunteer-interests{grid-template-columns:1fr}#call-to-action-partecipa .cta-partecipa-box{padding:24px 20px}#call-to-action-partecipa .cta-partecipa-text{font-size:16px}}
</style>

<script>
(function(){
    var form=document.getElementById('volunteerSignupForm');
    if(!form)return;
    var message=document.getElementById('volunteerSignupMessage');
    var button=form.querySelector('button[type="submit"]');
    form.addEventListener('submit',function(event){
        event.preventDefault();
        if(!form.checkValidity()){form.reportValidity();return;}
        button.disabled=true;message.className='volunteer-form-message';message.textContent='';
        fetch(form.action,{method:'POST',body:new FormData(form),headers:{Accept:'application/json'},credentials:'same-origin'})
            .then(function(response){return response.json().then(function(data){return {ok:response.ok,data:data};});})
            .then(function(result){
                message.textContent=result.data.message||'';
                message.className='volunteer-form-message is-visible'+(result.ok&&result.data.success?'':' is-error');
                if(result.ok&&result.data.success)form.reset();
            })
            .catch(function(){message.textContent=<?= json_encode(site_text('volunteer.error.network', null, 'Connessione non disponibile. Riprova.'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;message.className='volunteer-form-message is-visible is-error';})
            .finally(function(){button.disabled=false;});
    });
})();
</script>
