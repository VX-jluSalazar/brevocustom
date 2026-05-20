<div class="brevocustom-admin">
  <div class="brevocustom-hero">
    <div>
      <p class="brevocustom-kicker">Brevo Custom Events</p>
      <h2>Configuracion del modulo</h2>
      <p class="brevocustom-muted">Base tecnica para enviar eventos ecommerce a Brevo sin bloquear los flujos criticos de PrestaShop.</p>
    </div>
    <div class="brevocustom-status-card">
      <span class="brevocustom-status-dot {if $brevocustom_status.enabled}is-on{else}is-off{/if}"></span>
      <strong>{if $brevocustom_status.enabled}Integracion activa{else}Integracion pausada{/if}</strong>
      <small>{if $brevocustom_status.api_key_configured}API key configurada{else}API key pendiente{/if}</small>
    </div>
  </div>

  <form method="post" action="{$brevocustom_form_action|escape:'html':'UTF-8'}" class="brevocustom-shell">
    <input type="hidden" name="{$brevocustom_submit_name|escape:'html':'UTF-8'}" value="1">

    <div class="brevocustom-tabs" role="tablist">
      <button type="button" class="brevocustom-tab" data-brevocustom-tab="overview">Resumen</button>
      <button type="button" class="brevocustom-tab" data-brevocustom-tab="brevo">Brevo</button>
      <button type="button" class="brevocustom-tab" data-brevocustom-tab="urls">URLs</button>
      <button type="button" class="brevocustom-tab" data-brevocustom-tab="automation">Automatizacion</button>
      <button type="button" class="brevocustom-tab" data-brevocustom-tab="logs">Logs</button>
    </div>

    <div class="brevocustom-panel is-active" data-brevocustom-panel="overview">
      <div class="brevocustom-grid">
        <label class="brevocustom-toggle-card">
          <span>
            <strong>Activar envio de eventos</strong>
            <small>Si esta apagado, el modulo conserva configuracion y logs sin enviar a Brevo.</small>
          </span>
          <span class="brevocustom-switch-control">
            <input type="hidden" name="BREVOCUSTOM_ENABLED" value="0">
            <input type="checkbox" name="BREVOCUSTOM_ENABLED" value="1" {if $brevocustom_values.BREVOCUSTOM_ENABLED}checked{/if}>
            <i class="brevocustom-switch-track"></i>
          </span>
        </label>

        <label class="brevocustom-toggle-card">
          <span>
            <strong>Modo debug</strong>
            <small>Guarda payloads mas completos para diagnostico. Usarlo con cuidado en produccion.</small>
          </span>
          <span class="brevocustom-switch-control">
            <input type="hidden" name="BREVOCUSTOM_DEBUG" value="0">
            <input type="checkbox" name="BREVOCUSTOM_DEBUG" value="1" {if $brevocustom_values.BREVOCUSTOM_DEBUG}checked{/if}>
            <i class="brevocustom-switch-track"></i>
          </span>
        </label>
      </div>

      <div class="brevocustom-health">
        <div>
          <span class="brevocustom-pill {if $brevocustom_status.log_table}is-good{else}is-bad{/if}">Tabla logs</span>
          <p>{if $brevocustom_status.log_table}Lista para registrar intentos de envio.{else}La tabla de logs no esta disponible.{/if}</p>
        </div>
        <div>
          <span class="brevocustom-pill {if $brevocustom_status.api_key_configured}is-good{else}is-warn{/if}">Brevo API</span>
          <p>{if $brevocustom_status.api_key_configured}Credencial guardada en configuracion.{else}Agrega la API key antes de activar envios.{/if}</p>
        </div>
      </div>
    </div>

    <div class="brevocustom-panel" data-brevocustom-panel="brevo">
      <div class="brevocustom-section-head">
        <h3>Conexion Brevo</h3>
        <p>Credenciales y comportamiento del cliente HTTP para `POST /v3/events`.</p>
      </div>

      <div class="brevocustom-field">
        <label for="BREVOCUSTOM_BREVO_API_KEY">API key de Brevo</label>
        <input id="BREVOCUSTOM_BREVO_API_KEY" type="password" name="BREVOCUSTOM_BREVO_API_KEY" value="{$brevocustom_values.BREVOCUSTOM_BREVO_API_KEY|escape:'html':'UTF-8'}" autocomplete="off">
        <small>Se envia como header `api-key`. Nunca se escribe en logs.</small>
      </div>
    </div>

    <div class="brevocustom-panel" data-brevocustom-panel="urls">
      <div class="brevocustom-section-head">
        <h3>Dominio y URLs</h3>
        <p>URLs reutilizadas por todos los payloads para tienda, contacto, reviews y recompra.</p>
      </div>

      <div class="brevocustom-field">
        <label for="BREVOCUSTOM_SHOP_URL">shop_url</label>
        <input id="BREVOCUSTOM_SHOP_URL" type="url" name="BREVOCUSTOM_SHOP_URL" value="{$brevocustom_values.BREVOCUSTOM_SHOP_URL|escape:'html':'UTF-8'}">
      </div>

      <div class="brevocustom-field">
        <label for="BREVOCUSTOM_SHOP_REVIEW_URL">shop_review_url</label>
        <input id="BREVOCUSTOM_SHOP_REVIEW_URL" type="url" name="BREVOCUSTOM_SHOP_REVIEW_URL" value="{$brevocustom_values.BREVOCUSTOM_SHOP_REVIEW_URL|escape:'html':'UTF-8'}">
      </div>

      <div class="brevocustom-field">
        <label for="BREVOCUSTOM_CONTACT_URL">contact_url</label>
        <input id="BREVOCUSTOM_CONTACT_URL" type="url" name="BREVOCUSTOM_CONTACT_URL" value="{$brevocustom_values.BREVOCUSTOM_CONTACT_URL|escape:'html':'UTF-8'}">
      </div>

      <div class="brevocustom-field">
        <label for="BREVOCUSTOM_REORDER_URL_PATTERN">reorder_url pattern</label>
        <input id="BREVOCUSTOM_REORDER_URL_PATTERN" type="text" name="BREVOCUSTOM_REORDER_URL_PATTERN" value="{$brevocustom_values.BREVOCUSTOM_REORDER_URL_PATTERN|escape:'html':'UTF-8'}">
        <small>Debe incluir el token `{ldelim}id_order{rdelim}` para construir la URL por pedido.</small>
      </div>

      <div class="brevocustom-custom-urls" data-brevocustom-custom-urls>
        <div class="brevocustom-section-head">
          <h3>URLs custom</h3>
          <p>Se agregan directamente como atributos dentro de `misc` usando la key configurada.</p>
        </div>

        <div class="brevocustom-custom-url-list" data-brevocustom-custom-url-list>
          {foreach from=$brevocustom_custom_urls item=custom_url}
            <div class="brevocustom-custom-url-row" data-brevocustom-custom-url-row>
              <div class="brevocustom-field">
                <label>key</label>
                <input type="text" name="custom_url_key[]" value="{$custom_url.key|escape:'html':'UTF-8'}" placeholder="faq_url">
              </div>
              <div class="brevocustom-field">
                <label>label</label>
                <input type="text" name="custom_url_label[]" value="{$custom_url.label|escape:'html':'UTF-8'}" placeholder="Preguntas frecuentes">
              </div>
              <div class="brevocustom-field">
                <label>url</label>
                <input type="url" name="custom_url_value[]" value="{$custom_url.url|escape:'html':'UTF-8'}" placeholder="https://tienda.com/faq">
              </div>
              <button type="button" class="brevocustom-icon-button" data-brevocustom-remove-url aria-label="Eliminar URL">x</button>
            </div>
          {/foreach}
        </div>

        <button type="button" class="brevocustom-secondary-button" data-brevocustom-add-url>Agregar URL</button>

        <template data-brevocustom-custom-url-template>
          <div class="brevocustom-custom-url-row" data-brevocustom-custom-url-row>
            <div class="brevocustom-field">
              <label>key</label>
              <input type="text" name="custom_url_key[]" value="" placeholder="faq_url">
            </div>
            <div class="brevocustom-field">
              <label>label</label>
              <input type="text" name="custom_url_label[]" value="" placeholder="Preguntas frecuentes">
            </div>
            <div class="brevocustom-field">
              <label>url</label>
              <input type="url" name="custom_url_value[]" value="" placeholder="https://tienda.com/faq">
            </div>
            <button type="button" class="brevocustom-icon-button" data-brevocustom-remove-url aria-label="Eliminar URL">x</button>
          </div>
        </template>
      </div>
    </div>

    <div class="brevocustom-panel" data-brevocustom-panel="automation">
      <div class="brevocustom-section-head">
        <h3>Automatizacion</h3>
        <p>Parametros que usaran cron, hooks y builders de payload en fases siguientes.</p>
      </div>

      <div class="brevocustom-field brevocustom-field-small">
        <label for="BREVOCUSTOM_ABANDONED_CART_DELAY_MINUTES">Minutos para carrito abandonado</label>
        <input id="BREVOCUSTOM_ABANDONED_CART_DELAY_MINUTES" type="number" min="15" step="5" name="BREVOCUSTOM_ABANDONED_CART_DELAY_MINUTES" value="{$brevocustom_values.BREVOCUSTOM_ABANDONED_CART_DELAY_MINUTES|intval}">
        <small>Valor minimo: 15 minutos.</small>
      </div>

      <div class="brevocustom-domain-list">
        <div><strong>Orden creada</strong><span>actionValidateOrder</span></div>
        <div><strong>Orden enviada</strong><span>actionOrderStatusPostUpdate, estado 4</span></div>
        <div><strong>Orden entregada</strong><span>actionOrderStatusPostUpdate, estado 5</span></div>
        <div><strong>Suscriptor</strong><span>ps_emailsubscription</span></div>
        <div><strong>Carrito abandonado</strong><span>Cron del modulo</span></div>
      </div>

      <div class="brevocustom-field">
        <label for="BREVOCUSTOM_CRON_URL">URL cron carrito abandonado</label>
        <input id="BREVOCUSTOM_CRON_URL" type="text" value="{$brevocustom_cron_url|escape:'html':'UTF-8'}" readonly>
        <small>Ejecuta esta URL desde cron para enviar `vx_abandoned_cart`.</small>
      </div>
    </div>

    <div class="brevocustom-panel" data-brevocustom-panel="logs">
      <div class="brevocustom-section-head">
        <h3>Ultimos logs</h3>
        <p>Vista rapida de los intentos registrados en `PREFIX_brevocustom_event_log`.</p>
      </div>

      {if $brevocustom_recent_logs|count}
        <div class="brevocustom-table-wrap">
          <table class="brevocustom-table">
            <thead>
              <tr>
                <th>Evento</th>
                <th>Objeto</th>
                <th>Email</th>
                <th>Estado</th>
                <th>HTTP</th>
                <th>Fecha</th>
              </tr>
            </thead>
            <tbody>
              {foreach from=$brevocustom_recent_logs item=log}
                <tr>
                  <td>{$log.event_name|escape:'html':'UTF-8'}</td>
                  <td>{$log.object_type|escape:'html':'UTF-8'} #{$log.object_id|escape:'html':'UTF-8'}</td>
                  <td>{$log.email|escape:'html':'UTF-8'}</td>
                  <td><span class="brevocustom-pill is-neutral">{$log.status|escape:'html':'UTF-8'}</span></td>
                  <td>{$log.http_code|escape:'html':'UTF-8'}</td>
                  <td>{$log.date_add|escape:'html':'UTF-8'}</td>
                </tr>
              {/foreach}
            </tbody>
          </table>
        </div>
      {else}
        <div class="brevocustom-empty">
          <strong>Sin logs todavia</strong>
          <p>Cuando el cliente Brevo envie eventos, los intentos apareceran aqui.</p>
        </div>
      {/if}
    </div>

    <div class="brevocustom-actions">
      <button type="submit" class="brevocustom-save">
        Guardar configuracion
      </button>
    </div>
  </form>
</div>
