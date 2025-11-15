<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@3.4.3/dist/tailwind.min.css">

<div class="min-h-screen bg-gray-50 flex items-center justify-center p-6">
	<div class="w-full max-w-md bg-white shadow rounded-lg p-6 space-y-6">
		<h1 class="text-2xl font-bold text-gray-800">{register.label.title}</h1>

		{register.error}

		<form id="billing-register-form"
		      class="space-y-4"
		      action="{register.action}"
		      method="post"
		      data-code-url="{register.code.url}"
		      data-cooldown="{register.cooldown}"
		      data-requires-code="{register.requires_code}"
		      data-phone-invalid="{register.msg.phone_invalid}"
		      data-status-default="{register.msg.status_default}"
		      data-status-wait="{register.msg.status_wait}"
		      data-code-sent="{register.msg.code_sent}"
		      data-error-general="{register.msg.error_general}">

			<input type="hidden" name="user_hash" value="{hash}">

			<div>
				<label class="block text-sm text-gray-700 mb-1" for="register-name">{register.label.name}</label>
				<input id="register-name" name="name" type="text" value="{register.value.name}"
				       class="w-full border rounded px-3 py-2 focus:outline-none focus:ring"
				       autocomplete="name" required>
			</div>

			<div>
				<label class="block text-sm text-gray-700 mb-1" for="register-phone">{register.label.phone}</label>
				<div class="flex flex-col gap-2 sm:flex-row">
					<input id="register-phone" name="phone" type="tel" value="{register.value.phone}"
					       class="w-full border rounded px-3 py-2 focus:outline-none focus:ring sm:flex-1"
					       placeholder="+7 (700) 000-00-00" autocomplete="tel" required>
					<button type="button" id="send-whatsapp-code"
					        class="w-full sm:w-auto whitespace-nowrap bg-green-600 text-white px-4 py-2 rounded disabled:opacity-60"
					        data-send-label="{register.button.send_code}">
						{register.button.send_code}
					</button>
				</div>
				<p id="verification-status" class="text-xs text-gray-500 mt-1 min-h-[1.25rem]"></p>
				<p id="otp-disabled-note" class="text-xs text-gray-500 mt-1 hidden">
					{register.code.disabled}
				</p>
			</div>

			<div id="otp-section">
				<label class="block text-sm text-gray-700 mb-1" for="verification-code">{register.label.code}</label>
				<input id="verification-code" name="code" type="text"
				       class="w-full border rounded px-3 py-2 focus:outline-none focus:ring"
				       maxlength="6" pattern="\d{6}" inputmode="numeric" autocomplete="one-time-code">
			</div>

			<div>
				<label class="block text-sm text-gray-700 mb-1" for="register-school">{register.label.school}</label>
				<input id="register-school" name="school" type="text" value="{register.value.school}"
				       class="w-full border rounded px-3 py-2 focus:outline-none focus:ring"
				       autocomplete="organization" required>
			</div>

			<div>
				<label class="block text-sm text-gray-700 mb-1" for="register-password">{register.label.password}</label>
				<input id="register-password" name="password" type="password"
				       class="w-full border rounded px-3 py-2 focus:outline-none focus:ring"
				       autocomplete="new-password" required minlength="8">
			</div>

			<button type="submit" name="register_submit"
			        class="w-full bg-blue-600 text-white rounded px-4 py-2 hover:bg-blue-700 transition">
				{register.label.button}
			</button>

			<p class="text-xs text-gray-500">{register.policy}</p>
		</form>

		<p class="text-sm text-gray-600">
			{register.label.have_account}
			<a href="{register.login.url}" class="text-blue-600 hover:underline">{register.label.login_here}</a>
		</p>
	</div>
</div>

<script>
(function() {
	const form = document.getElementById('billing-register-form');
	if (!form) return;

	const phoneInput = document.getElementById('register-phone');
	const sendButton = document.getElementById('send-whatsapp-code');
	const statusEl = document.getElementById('verification-status');
	const otpSection = document.getElementById('otp-section');
	const otpNote = document.getElementById('otp-disabled-note');
	const codeInput = document.getElementById('verification-code');
	const requiresCode = form.dataset.requiresCode === '1';
	const cooldown = Number(form.dataset.cooldown) || 60;
	const messages = {
		phoneInvalid: form.dataset.phoneInvalid || '',
		statusDefault: form.dataset.statusDefault || '',
		statusWait: form.dataset.statusWait || '',
		codeSent: form.dataset.codeSent || '',
		errorGeneral: form.dataset.errorGeneral || ''
	};
	let timer = null;
	let remaining = 0;

	const setStatus = (text, variant = 'muted') => {
		if (!statusEl) return;
		statusEl.textContent = text || '';
		statusEl.classList.remove('text-gray-500', 'text-red-600', 'text-green-600');
		const map = { muted: 'text-gray-500', error: 'text-red-600', success: 'text-green-600' };
		statusEl.classList.add(map[variant] || map.muted);
	};

	const digits = (value) => (value || '').replace(/\D/g, '');

	const formatKZ = (value) => {
		if (!value) return '';
		if (value[0] === '8') value = '7' + value.slice(1);
		if (value[0] !== '7') value = '7' + value;
		value = value.slice(0, 11);
		const rest = value.slice(1);
		let out = '+7 ';
		if (rest.length > 0) out += '(' + rest.slice(0, 3);
		if (rest.length >= 3) out += ') ';
		if (rest.length > 3) out += rest.slice(3, 6);
		if (rest.length >= 6) out += '-' + rest.slice(6, 8);
		if (rest.length >= 8) out += '-' + rest.slice(8, 10);
		return out.trim();
	};

	const toE164 = (value) => {
		let d = digits(value);
		if (!d) return '';
		if (d[0] === '8') d = '7' + d.slice(1);
		if (d[0] !== '7') d = '7' + d;
		d = d.slice(0, 11);
		return d.length === 11 ? '+' + d : '';
	};

	const updateButtonLabel = () => {
		if (!sendButton) return;
		if (remaining > 0) {
			sendButton.textContent = `${messages.statusDefault} (${remaining}с)`;
		} else {
			sendButton.textContent = sendButton.dataset.sendLabel || messages.statusDefault;
		}
	};

	const startCooldown = () => {
		if (!sendButton) return;
		remaining = cooldown;
		sendButton.disabled = true;
		updateButtonLabel();
		if (timer) clearInterval(timer);
		timer = setInterval(() => {
			remaining -= 1;
			if (remaining <= 0) {
				clearInterval(timer);
				timer = null;
				sendButton.disabled = false;
				remaining = 0;
			}
			updateButtonLabel();
		}, 1000);
	};

	const disableOtpUi = () => {
		if (otpNote) otpNote.classList.remove('hidden');
		if (otpSection) otpSection.classList.add('hidden');
		if (codeInput) {
			codeInput.value = '';
			codeInput.removeAttribute('required');
		}
		if (sendButton) sendButton.classList.add('hidden');
	};

	if (!requiresCode) {
		disableOtpUi();
	} else if (codeInput) {
		codeInput.setAttribute('required', 'required');
	}

	if (phoneInput) {
		phoneInput.addEventListener('input', () => {
			const caret = phoneInput.selectionStart;
			phoneInput.value = formatKZ(digits(phoneInput.value));
			if (caret !== null) {
				phoneInput.setSelectionRange(caret, caret);
			}
		});
		phoneInput.addEventListener('blur', () => { phoneInput.value = formatKZ(digits(phoneInput.value)); });
		form.addEventListener('submit', () => { phoneInput.value = toE164(phoneInput.value); });
		phoneInput.addEventListener('paste', () => setTimeout(() => { phoneInput.value = formatKZ(digits(phoneInput.value)); }, 0));
	}

	const sendCode = async () => {
		if (!sendButton || !phoneInput) return;
		const phone = toE164(phoneInput.value);
		if (!phone) {
			setStatus(messages.phoneInvalid, 'error');
			return;
		}

		setStatus(messages.statusWait, 'muted');
		sendButton.disabled = true;
		sendButton.textContent = messages.statusWait;

		try {
			const response = await fetch(form.dataset.codeUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'Accept': 'application/json'
				},
				body: JSON.stringify({
					phone,
					user_hash: form.querySelector('input[name="user_hash"]').value
				})
			});

			const payload = await response.json().catch(() => ({}));

			if (!response.ok) {
				throw new Error(payload?.message || messages.errorGeneral);
			}

			if (payload?.requires_code === false) {
				disableOtpUi();
				setStatus(payload?.message || '', 'muted');
				return;
			}

			if (codeInput) {
				codeInput.setAttribute('required', 'required');
				if (otpSection) otpSection.classList.remove('hidden');
			}

			setStatus(payload?.message || messages.codeSent, 'success');
			startCooldown();
		} catch (error) {
			sendButton.disabled = false;
			setStatus(error.message || messages.errorGeneral, 'error');
			updateButtonLabel();
		}
	};

	sendButton?.addEventListener('click', sendCode);
	updateButtonLabel();
})();
</script>
