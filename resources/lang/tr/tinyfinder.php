<?php

return [
    // Buttons
    'button_close' => 'Kapat',
    'button_okay' => 'Tamam',
    'button_cancel' => 'İptal',
    'button_new' => 'Yeni',
    'button_archive' => 'Arşiv',
    'button_search_in_archive' => 'Arşivden seç',
    'button_add_video' => 'Videoyu Ekle',
    'button_copy_to_clipboard' => 'Linki Panoya Kopyala',
    'button_upload_files' => 'Dosya Yükle',
    'button_download' => 'İndir',
    'button_delete' => 'Sil',
    'button_crop' => 'Kırp',
    'button_resize' => 'Boyutlandır',
    'button_rotate' => 'Döndür',

    // Alerts
    'alert_not_found' => 'İşlem yapmak istediğiniz dosyaya erişilemiyor/kaldırılmış.',
    'alert_srv_not_responding' => 'Sunucu yanıt vermiyor.',
    'alert_min_dimensions' => 'İzin verilen en küçük resim boyutu',
    'alert_not_selected_file' => 'Lütfen listeden bir dosya seçiniz.',
    'alert_type_not_allowed' => 'Atlanan dosya(lar):',
    'alert_larger_than_max_size' => 'Desteklenen maksimum dosya boyutundan büyük',
    'alert_wait_current_process' => 'Lütfen mevcut işlemin tamamlanmasını bekleyin.',
    'alert_delete_confirm' => 'Dosya kalıcı olarak silinecek, devam edilsin mi?',
    'alert_delete_success' => 'Dosya başarıyla silindi',
    'alert_upload_success' => '{count} dosya başarıyla yüklendi',
    'alert_crop_success' => 'Resim başarıyla kırpıldı',
    'alert_resize_success' => 'Resim başarıyla boyutlandırıldı',
    'alert_rotate_success' => 'Resim başarıyla döndürüldü',

    // Text
    'text_processing' => 'Yükleniyor..',
    'text_wait' => 'Lütfen Bekleyiniz...',
    'text_process_failed' => 'İşleminiz başarısız.',
    'text_cropping_tool' => 'Resim Kırpma Aracı',
    'text_rename' => 'Yeniden Adlandır',
    'text_resizing_option' => 'Boyutlandırma Tercihi',
    'text_create_thumbs' => 'Küçükleri oluştur',
    'text_private_upload' => 'Bana özel yükle',
    'text_private_upload_title' => 'yalnızca sizin listenizde listelensin',
    'text_video_options' => 'Video Tercihleri',
    'text_width' => 'Genişlik',
    'text_height' => 'Yükseklik',
    'text_controls' => 'Kontroller',
    'text_autoplay' => 'Otomatik Oynat',
    'text_muted' => 'Sessiz',
    'text_private' => 'Özel',
    'text_public' => 'Genel',
    'text_file_url' => 'Dosya Url',
    'text_download' => 'İndir',
    'text_upload_img' => 'Resim Yükle',
    'text_upload_file' => 'Dosya Yükle',
    'text_uploading_files' => 'Yükleme yapılıyor. Lütfen bekleyiniz ...',
    'text_file_not_found' => 'Dosya Bulunamadı',
    'text_url_copied' => 'URL panoya kopyalandı!',
    'text_copy_failed' => 'URL kopyalanamadı',

    // Resize Types
    'resize_no' => 'Boyutlandırma.',
    'resize_standard' => 'Standart boyutlandır.',
    'resize_keep_ratio' => 'Boyutlandır ve oranı koru.',
    'resize_crop_center' => 'Boyutlandır, resmi ortalayarak fazlalığı kırp.',
    'resize_fill_blanks' => 'Boyutlandır, oranı korumak için kenar boşlukları oluştur.',

    // File Types
    'type_image' => 'Resim',
    'type_file' => 'Dosya',
    'type_images' => 'Resimler',
    'type_files' => 'Dosyalar',

    // Resource
    'resource_label' => 'Dosya Yöneticisi',
    'resource_plural_label' => 'Dosyalar',
    'navigation_label' => 'Dosya Yöneticisi',

    // Table Columns
    'column_preview' => 'Önizleme',
    'column_name' => 'Ad',
    'column_type' => 'Tür',
    'column_extension' => 'Uzantı',
    'column_size' => 'Boyut',
    'column_thumbs' => 'Küçük Resimler',
    'column_private' => 'Özel',
    'column_owner' => 'Sahip',
    'column_created_at' => 'Oluşturulma',

    // Filters
    'filter_type' => 'Tür',
    'filter_extension' => 'Uzantı',
    'filter_privacy' => 'Gizlilik',
    'filter_all_files' => 'Tüm dosyalar',
    'filter_private_only' => 'Sadece özel',
    'filter_public_only' => 'Sadece genel',
    'filter_my_files' => 'Dosyalarım',

    // Actions
    'action_download' => 'İndir',
    'action_copy_url' => 'URL Kopyala',
    'action_edit' => 'Düzenle',
    'action_delete' => 'Sil',
    'action_make_private' => 'Özel Yap',
    'action_make_public' => 'Genel Yap',
    'action_crop_image' => 'Resmi Kırp',
    'action_resize_image' => 'Resmi Boyutlandır',
    'action_rotate_image' => 'Resmi Döndür',

    // Form
    'form_file_info' => 'Dosya Bilgisi',
    'form_image_dimensions' => 'Resim Boyutları',
    'form_settings' => 'Ayarlar',
    'form_preview' => 'Önizleme',
    'form_private_file' => 'Özel Dosya',
    'form_has_thumbnails' => 'Küçük Resimleri Var',

    // Upload
    'upload_drag_drop' => 'Dosyaları buraya sürükleyip bırakın veya göz atmak için tıklayın',
    'upload_max_files' => 'Maksimum {count} dosya',
    'upload_max_size' => 'Maksimum dosya boyutu: {size}',
    'upload_allowed_types' => 'İzin verilen türler: {types}',

    // Notifications
    'notification_success' => 'Başarılı!',
    'notification_error' => 'Hata!',
    'notification_warning' => 'Uyarı!',
    'notification_info' => 'Bilgi',

    // Validation
    'validation_required' => 'Bu alan gereklidir',
    'validation_min' => 'Minimum değer {min}',
    'validation_max' => 'Maksimum değer {max}',
    'validation_image' => 'Dosya bir resim olmalıdır',
    'validation_mimes' => 'Geçersiz dosya türü',
];
