<?php

/**
 * Get current language code from session or default
 */
function getCurrentLanguage() {
    global $pdo;
    
    // If language is set in session, use that
    if (isset($_SESSION['language'])) {
        return $_SESSION['language'];
    }
    
    // Otherwise get default language from database
    $stmt = $pdo->query("SELECT code FROM languages WHERE is_default = 1 LIMIT 1");
    $defaultLang = $stmt->fetchColumn();
    
    // Set it in session
    $_SESSION['language'] = $defaultLang ?: 'hu';
    
    return $_SESSION['language'];
}

/**
 * Get translation for a key in current language
 */
function __t($key, $context = 'shop', $params = []) {
    global $pdo;
    $lang = getCurrentLanguage();
    
    // Get translation from database
    $stmt = $pdo->prepare("SELECT translation_value FROM translations 
                          WHERE language_code = ? AND translation_key = ? AND context = ?");
    $stmt->execute([$lang, $key, $context]);
    $translation = $stmt->fetchColumn();
    
    // If translation not found in current language, try default language
    if (!$translation && $lang !== 'hu') {
        $stmt->execute(['hu', $key, $context]);
        $translation = $stmt->fetchColumn();
    }
    
    // If still no translation, return the key
    if (!$translation) {
        return $key;
    }
    
    // Replace parameters if any
    if (!empty($params)) {
        foreach ($params as $param => $value) {
            $translation = str_replace('{'.$param.'}', $value, $translation);
        }
    }
    
    return $translation;
}

/**
 * Get all available languages
 */
function getAvailableLanguages() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT * FROM languages WHERE is_active = 1 ORDER BY is_default DESC, name ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Set current language
 */
function setLanguage($code) {
    global $pdo;
    
    // Verify language exists and is active
    $stmt = $pdo->prepare("SELECT code FROM languages WHERE code = ? AND is_active = 1");
    $stmt->execute([$code]);
    
    if ($stmt->fetchColumn()) {
        $_SESSION['language'] = $code;
        return true;
    }
    
    return false;
}

/**
 * Get translation for a specific entity (product, category)
 */
function getEntityTranslation($entityType, $entityId, $field, $fallbackValue = '') {
    global $pdo;
    $lang = getCurrentLanguage();
    
    $table = $entityType . '_translations';
    $stmt = $pdo->prepare("SELECT $field FROM $table WHERE {$entityType}_id = ? AND language_code = ?");
    $stmt->execute([$entityId, $lang]);
    $translation = $stmt->fetchColumn();
    
    // If no translation in current language, try default language
    if (!$translation && $lang !== 'hu') {
        $stmt->execute([$entityId, 'hu']);
        $translation = $stmt->fetchColumn();
    }
    
    return $translation ?: $fallbackValue;
}
