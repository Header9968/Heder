<?php

return array
(
    'coupon_use_error' => "Ошибка применения купона",

    /* 0.8 */
	'hash_error' => "Время ожидания модуля закончилось. Повторите попытку",
    'pay_handler_desc' => "Описание:",
    'title_short' => "Баланс пользователя",

	/* 0.7.4 */
	'invoice_max_num' => "Вы имеете больше %s неоплаченных квитанций, просим вас их оплатить или удалить.",
	'invoice_good_desc' => "Пополнение баланса",
    'invoice_good_desc2' => "Оплата услуг",
	'invoice_paid_error' => "Квитанция уже оплачена, невозможно удалить.",
	'pay_incorect_sum' => "Введите корректную сумму.",
	'pay_invoice_now' => "Оплатить",

	/* 0.7 */
	'handler_error_id' => "Номер квитанции не получен",

	/* Cabinet */
	'cabinet_off' => "Личный кабинет отключен",
	'cabinet_controller_error' => "Файл плагина user.%s не найден!",
	'cabinet_metod_error' => "Метод плагина user.%s->%s не найден!",
	'cabinet_theme_error' => "Невозможно загрузить шаблон ",
    'main_now' => "Сегодня в ",
    'main_rnow' => "Вчера в ",

	/* Pay */
	'pay_need_login' => "Требуется авторизация!",
	'pay_hash_error' => "Время ожидания модуля закончилось. Повторите попытку",
	'pay_paysys_error' => "Не выбран способ оплаты",
	'pay_sum_error' => "Недостаточно средств на балансе. <b><a href=/billing.html/pay/>Пополнить баланс</a></b>",
	'pay_summa_error' => "Не указана сумма",
	'pay_minimum_error' => "Минимальная сумма оплаты для %s составляет %s %s",
	'pay_max_error' => "Максимальная сумма оплаты для %s составляет %s %s",
	'pay_main_error' => "Пополнение баланса не доступно",
	'pay_invoice_error' => "Квитанция не найдена",
	'pay_invoice_pay' => "Квитанция уже оплачена",
	'pay_invoice_payment' => "Платежная система не соответствует указанной в квитанции",
	'pay_file_error' => "Ошибка инициализации платежного агрегатора!",
	'pay_balance' => "Оплата с баланса",

	'pay_invoice' => "Квитанция #{id}",
	'pay_msgOk' => "Пополнения баланса через %s на %s %s",

	'pay_error_title' => "Ошибка",

	'pay_getErr_key' => "Токен доступа устарел",
	'pay_getErr_paysys' => "Платежная система не найдена",
	'pay_getErr_invoice' => "Квитанция не найдена, либо уже оплачена",
	'pay_desc' => "Пополнение баланса пользователя %s на сумму %s %s",

	/* Refund */
	'refund_error_requisites' => "Не указаны <a href=\"\">реквизиты</a>",
	'refund_error_balance' => "Недостаточно средств. <a href=/billing.html/pay/>Пополнить баланс</a>",
	'refund_error_minimum' => "Минимальная сумма для вывода - %s %s",
	'refund_msgOk' => "Вывод средств из системы, номер запроса - %s",
	'refund_wait' => "Ожидается",
    'refund_ok' => "Выполнен",
    'refund_cancel' => "Отменен",
	'refund_email_title' => "Запрос вывода средств",
	'refund_email_msg' => "Пользователь %s запросил вывод средств в размере %s %s на реквизиты %s.<br /><br />Подробнее - %s",
	'refund_ok_title' => "Запрос создан",
	'refund_ok_text' => "Ваш запрос  вывода средств создан. В ближайшее время его рассмотрит администратор. <br>",

	/* Transfer */
	'transfer_error_get' => "Получатель не найден",
	'transfer_error_minimum' => "Минимальная сумма для перевода - %s %s <br>",
	'transfer_error_name_me' => "Вы не можете отправить средства самому себе",

	'transfer_log_for' => "Перевод средств для <a href=/user/%s>%s</a>, из них комиссия - %s %s",
	'transfer_log_from' => "Перевод средств от <a href=/user/%s>%s</a>",
	'transfer_log_text' => "Перевод для пользователя <a href=\"/user/%s\">%s</a> выполнен. Комиссия составила %s %s<br>",
	'transfer_msgOk' => "Перевод отправлен",

	/* Bonus */
	'bonus_first_comment' => "Бонус первого пополнения баланса",
	'bonus_comment' => "Бонус пополнения баланса",

	/* Register */
	'register_title' => "Регистрация",
	'register_name' => "Имя",
	'register_name_required' => "Укажите ваше имя.",
	'register_phone' => "Телефон",
	'register_school' => "Школа",
	'register_school_required' => "Укажите школу.",
	'register_password' => "Пароль",
	'register_code' => "Код подтверждения",
	'register_send_code' => "Отправить код",
	'register_code_sent' => "Код отправлен. Проверьте WhatsApp.",
	'register_code_disabled' => "WhatsApp временно недоступен. Регистрация возможна без подтверждения номера.",
	'register_phone_invalid' => "Введите корректный номер телефона в формате +7 (XXX) XXX-XX-XX.",
	'register_code_invalid' => "Указан неверный код подтверждения.",
	'register_code_required' => "Введите код из WhatsApp.",
	'register_code_expired' => "Срок действия кода истёк, запросите новый.",
	'register_code_blocked' => "Отправка кодов временно заблокирована. Повторите попытку позже.",
	'register_password_rule' => "Пароль должен содержать не менее 8 символов.",
	'register_error_exists' => "Пользователь с таким номером уже зарегистрирован.",
	'register_error_general' => "Не удалось завершить регистрацию. Попробуйте позже.",
	'register_button' => "Создать аккаунт",
	'register_have_account' => "Уже есть аккаунт?",
	'register_login_here' => "Войти",
	'register_policy' => "Нажимая кнопку, вы соглашаетесь на обработку персональных данных.",
	'register_success_title' => "Аккаунт создан",
	'register_success_body' => "Регистрация завершена. Вход выполняется по номеру телефона.",
	'register_success_login_hint' => "Теперь вы можете <a href=\"%s\">войти</a> в аккаунт.",
	'register_rate_limited' => "Вы слишком часто запрашиваете код. Повторите через %s сек.",
	'register_status_wait' => "Отправляем код...",
	'register_status_default' => "Отправить код",

	'off' => "<div style='background-color: #ff2e2e; color: white; padding: 10px; margin: 5px; border-radius: 5px'>Личный кабинет отключен для всех, кроме администраторов. <br />Включите модуль в админ.панели: Баланс пользователя &rarr; Настройки.</div>"

);