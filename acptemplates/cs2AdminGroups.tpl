{include file='header' pageTitle='wcf.acp.cs2admin.groups'}

<header class="contentHeader">
	<div class="contentHeaderTitle">
		<h1 class="contentTitle">{lang}wcf.acp.cs2admin.groups{/lang}</h1>
	</div>
</header>

{include file='shared_formError'}

{if $success|isset}
	<woltlab-core-notice type="success">{lang}wcf.global.success.edit{/lang}</woltlab-core-notice>
{/if}
{if $syncResult}
	<woltlab-core-notice type="success">{lang admins=$syncResult.count skipped=$syncResult.skipped}wcf.acp.cs2admin.sync.success{/lang}</woltlab-core-notice>
{/if}
{if $syncError}
	<woltlab-core-notice type="error">{$syncError}</woltlab-core-notice>
{/if}

<woltlab-core-notice type="info">{lang}wcf.acp.cs2admin.info{/lang}</woltlab-core-notice>

<form method="post" action="{link controller='Cs2AdminGroups'}{/link}">
	{foreach from=$groups key=groupID item=group}
		<section class="section">
			<h2 class="sectionTitle">{$group->getName()}</h2>

			<dl>
				<dt></dt>
				<dd>
					<label><input type="checkbox" name="rows[{@$groupID}][enabled]" value="1"{if $view[$groupID][enabled]} checked{/if}> {lang}wcf.acp.cs2admin.enabled{/lang}</label>
				</dd>
			</dl>

			<dl>
				<dt><label for="prio{@$groupID}">{lang}wcf.acp.cs2admin.priority{/lang}</label></dt>
				<dd>
					<input type="number" id="prio{@$groupID}" name="rows[{@$groupID}][priority]" value="{@$view[$groupID][priority]}" class="tiny">
					<small>{lang}wcf.acp.cs2admin.priority.description{/lang}</small>
				</dd>
			</dl>

			<dl>
				<dt><label for="imm{@$groupID}">{lang}wcf.acp.cs2admin.immunity{/lang}</label></dt>
				<dd>
					<input type="number" id="imm{@$groupID}" name="rows[{@$groupID}][immunity]" value="{@$view[$groupID][immunity]}" min="0" max="255" class="tiny">
				</dd>
			</dl>

			<dl>
				<dt>{lang}wcf.acp.cs2admin.flags{/lang}</dt>
				<dd>
					{foreach from=$allFlags item=flag}
						<label><input type="checkbox" name="rows[{@$groupID}][flags][]" value="{$flag}"{if $view[$groupID][flags][$flag]} checked{/if}> {$flag}</label><br>
					{/foreach}
				</dd>
			</dl>
		</section>
	{/foreach}

	<div class="formSubmit">
		<input type="submit" value="{lang}wcf.global.button.submit{/lang}" accesskey="s">
		<button type="submit" name="sync" value="1">{lang}wcf.acp.cs2admin.saveAndSync{/lang}</button>
		{csrfToken}
	</div>
</form>

{include file='footer'}
