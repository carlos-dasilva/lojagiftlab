<?php

return [
    'groups' => [
        'identity' => ['Identidade', [
            'site_name' => ['Nome público', 'text', 'Gift Lab'], 'site_email' => ['E-mail', 'email', 'lojagiftlab@gmail.com'],
            'slogan' => ['Slogan', 'text', ''], 'primary_color' => ['Azul principal', 'color', '#0B163D'],
            'teal_color' => ['Turquesa', 'color', '#08B9B3'], 'purple_color' => ['Roxo', 'color', '#7139C6'],
            'coral_color' => ['Coral', 'color', '#FF3D5F'], 'yellow_color' => ['Amarelo', 'color', '#FFB62B'],
            'logo' => ['Logo', 'file', ''], 'favicon' => ['Ícone do site', 'file', ''], 'social_image' => ['Imagem social padrão', 'file', ''],
        ]],
        'contact' => ['Contato e empresa', [
            'phone' => ['Telefone', 'text', ''], 'whatsapp' => ['WhatsApp (país e DDD)', 'text', ''],
            'address' => ['Endereço', 'text', ''], 'city' => ['Cidade', 'text', ''], 'state' => ['Estado', 'text', ''],
            'postal_code' => ['CEP', 'text', ''], 'opening_hours' => ['Horário de atendimento', 'text', ''],
            'company_name' => ['Razão social', 'text', ''], 'cnpj' => ['CNPJ', 'text', ''], 'responsible' => ['Responsável', 'text', ''],
            'contact_email_enabled' => ['Enviar notificações de contato por e-mail (SMTP configurado no servidor)', 'checkbox', '0'],
        ]],
        'social' => ['Redes sociais', [
            'instagram' => ['Instagram', 'url', ''], 'facebook' => ['Facebook', 'url', ''], 'tiktok' => ['TikTok', 'url', ''],
            'youtube' => ['YouTube', 'url', ''], 'pinterest' => ['Pinterest', 'url', ''],
        ]],
        'home' => ['Página inicial', [
            'hero_title' => ['Título do hero', 'text', 'Presentes, criatividade e coisas incríveis ganhando forma.'],
            'hero_subtitle' => ['Subtítulo', 'textarea', 'Ideias especiais, itens geek e presentes únicos escolhidos para surpreender.'],
            'hero_image' => ['Imagem do hero', 'file', ''], 'hero_cta' => ['Texto do botão', 'text', 'Explorar produtos'],
            'hero_url' => ['URL do botão (opcional)', 'url', ''],
            'home_story' => ['Texto institucional', 'textarea', 'Do universo geek à decoração, cada descoberta é escolhida para surpreender.'],
            'home_categories' => ['Exibir categorias', 'checkbox', '1'], 'home_featured' => ['Exibir destaques', 'checkbox', '1'],
            'home_new' => ['Exibir lançamentos', 'checkbox', '1'], 'home_banners' => ['Exibir banners', 'checkbox', '1'],
            'home_promotions' => ['Exibir promoções', 'checkbox', '1'], 'home_customizable' => ['Exibir personalizáveis', 'checkbox', '1'],
            'home_popular' => ['Exibir mais vistos', 'checkbox', '1'], 'home_about' => ['Exibir bloco institucional', 'checkbox', '1'],
            'home_social' => ['Exibir chamadas de contato', 'checkbox', '1'],
            'title_featured' => ['Título dos destaques', 'text', 'Destaques do laboratório'],
            'title_new' => ['Título dos lançamentos', 'text', 'Acabaram de chegar'],
            'title_promotions' => ['Título das promoções', 'text', 'Preços especiais'],
            'title_customizable' => ['Título dos personalizáveis', 'text', 'Do seu jeito'],
            'title_popular' => ['Título dos mais vistos', 'text', 'Os mais vistos'],
        ]],
        'seo' => ['Busca e compartilhamento', [
            'seo_title' => ['Título padrão', 'text', 'Gift Lab — presentes e criatividade'],
            'seo_description' => ['Descrição padrão', 'textarea', 'Presentes, itens geek e coisas incríveis ganhando forma na Gift Lab.'],
        ]],
    ],
];
