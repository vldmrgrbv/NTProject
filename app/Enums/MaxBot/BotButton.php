<?php

namespace App\Enums\MaxBot;

enum BotButton: string
{
    case CHECK_EYESIGHT = 'check_eyesight';
    case TRY_LENSES = 'try_lenses';
    case ABOUT_PRODUCTS = 'about_products';
    case WHERE_TO_BUY = 'where_to_buy';
    case ASK_QUESTION = 'ask_question';
    case CATALOG_PRODUCTS = 'catalog_products';
    case CONTACT = 'contact';
    case SHOP_NT = 'shop_nt';
    case SEARCH_OPTICS = 'search_optics';
    case CLUB_PRIVILEGES = 'club_privileges';
    case SPEND_POINTS = 'spend_points';
    case GET_CASHBACK = 'get_cashback';
    case EXCHANGE_POINTS = 'exchange_points';
    case UPLOAD_RECEIPT = 'upload_receipt';
    case SCAN_RECEIPT = 'scan_receipt';
    case LOG_IN = 'log_in';
    case SCORE_BALANCE = 'score_balance';
    case SCORE_BALANCE_LESS = 'score_balance_less';
    case SCORE_BALANCE_MORE = 'score_balance_more';
    case STICKER_PACK = 'sticker_pack';
    case STICKER_PACK_SAVE = 'sticker_pack_save';
    case MAIN = 'main';

    public function label(): string
    {
        return __('bot.buttons.'.$this->value);
    }

    public function responseText(): ?string
    {
        $key = 'bot.responses.'.$this->value.'.text';
        $translation = __($key);

        return $translation === $key ? null : $translation;
    }

    public function responseUrl(): ?string
    {
        $key = 'bot.responses.'.$this->value.'.url';
        $translation = __($key);

        return $translation === $key ? null : $translation;
    }

    public function responseButtons(): array
    {
        $key = 'bot.responses.'.$this->value.'.buttons';
        $buttons = __($key);

        return is_array($buttons) ? $buttons : [];
    }
}
