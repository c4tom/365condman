<?php
/**
 * API Endpoints para o Sistema de Gestão de Condomínio
 */

// Registrar rotas da API
add_action('rest_api_init', function () {
    // Endpoints para Condomínios
    register_rest_route('365condman/v1', '/condominiums', [
        'methods' => 'GET',
        'callback' => 'g360_get_condominiums',
        'permission_callback' => 'g360_check_admin_permission',
    ]);
    
    register_rest_route('365condman/v1', '/condominiums', [
        'methods' => 'POST',
        'callback' => 'g360_create_condominium',
        'permission_callback' => 'g360_check_admin_permission',
    ]);

    // Endpoints para Unidades
    register_rest_route('365condman/v1', '/units', [
        'methods' => 'GET',
        'callback' => 'g360_get_units',
        'permission_callback' => 'g360_check_permission',
    ]);

    register_rest_route('365condman/v1', '/units', [
        'methods' => 'POST',
        'callback' => 'g360_create_unit',
        'permission_callback' => 'g360_check_admin_permission',
    ]);

    // Endpoints para Moradores
    register_rest_route('365condman/v1', '/residents', [
        'methods' => 'GET',
        'callback' => 'g360_get_residents',
        'permission_callback' => 'g360_check_permission',
    ]);

    register_rest_route('365condman/v1', '/residents', [
        'methods' => 'POST',
        'callback' => 'g360_create_resident',
        'permission_callback' => 'g360_check_admin_permission',
    ]);

    // Endpoints para Cobranças
    register_rest_route('365condman/v1', '/charges', [
        'methods' => 'GET',
        'callback' => 'g360_get_charges',
        'permission_callback' => 'g360_check_permission',
    ]);

    register_rest_route('365condman/v1', '/charges', [
        'methods' => 'POST',
        'callback' => 'g360_create_charge',
        'permission_callback' => 'g360_check_admin_permission',
    ]);

    // Endpoints para Áreas Comuns
    register_rest_route('365condman/v1', '/common-areas', [
        'methods' => 'GET',
        'callback' => 'g360_get_common_areas',
        'permission_callback' => 'g360_check_permission',
    ]);

    // Endpoints para Reservas
    register_rest_route('365condman/v1', '/bookings', [
        'methods' => 'GET',
        'callback' => 'g360_get_bookings',
        'permission_callback' => 'g360_check_permission',
    ]);

    register_rest_route('365condman/v1', '/bookings', [
        'methods' => 'POST',
        'callback' => 'g360_create_booking',
        'permission_callback' => 'g360_check_permission',
    ]);

    // Endpoints para Ocorrências
    register_rest_route('365condman/v1', '/incidents', [
        'methods' => 'GET',
        'callback' => 'g360_get_incidents',
        'permission_callback' => 'g360_check_permission',
    ]);

    register_rest_route('365condman/v1', '/incidents', [
        'methods' => 'POST',
        'callback' => 'g360_create_incident',
        'permission_callback' => 'g360_check_permission',
    ]);
});

// Funções de verificação de permissão
function g360_check_permission($request) {
    return current_user_can('read');
}

function g360_check_admin_permission($request) {
    return current_user_can('manage_options');
}

// Implementações dos callbacks (a serem desenvolvidas)
function g360_get_condominiums($request) {
    // TODO: Implementar lógica de busca de condomínios
    return new WP_REST_Response(['message' => 'Not implemented yet'], 501);
}

function g360_create_condominium($request) {
    // TODO: Implementar lógica de criação de condomínio
    return new WP_REST_Response(['message' => 'Not implemented yet'], 501);
}

function g360_get_units($request) {
    // TODO: Implementar lógica de busca de unidades
    return new WP_REST_Response(['message' => 'Not implemented yet'], 501);
}

function g360_create_unit($request) {
    // TODO: Implementar lógica de criação de unidade
    return new WP_REST_Response(['message' => 'Not implemented yet'], 501);
}

// ... Implementar demais funções de callback
