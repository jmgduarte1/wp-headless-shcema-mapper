<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Rest;

use Throwable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class FormController
{
    private const NAMESPACE = 'headless-renderer/v1';

    public function registerRoutes(): void
    {
        register_rest_route(self::NAMESPACE, '/forms/(?P<form_id>\d+)/submit', [
            'methods' => 'POST',
            'callback' => [$this, 'submit'],
            'permission_callback' => '__return_true',
            'args' => [
                'form_id' => ['required' => true, 'sanitize_callback' => 'absint'],
                'values' => ['required' => true],
            ],
        ]);
    }

    public function submit(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $formId = absint((string) $request->get_param('form_id'));
        $values = $request->get_param('values');
        $nonce = sanitize_text_field((string) $request->get_param('nonce'));

        if ($formId < 1 || !is_array($values) || !function_exists('wpforms') || !wp_verify_nonce($nonce, 'wpforms::form_' . $formId)) {
            return new WP_Error('headless_form_invalid_request', 'Invalid form submission.', ['status' => 400]);
        }

        $form = wpforms()->obj('form');
        $formData = $form ? $form->get($formId, ['content_only' => true]) : false;
        if (!is_array($formData) || !is_array($formData['fields'] ?? null)) {
            return new WP_Error('headless_form_not_found', 'Form not found.', ['status' => 404]);
        }

        $entry = ['id' => $formId, 'nonce' => $nonce, 'fields' => []];
        foreach ($formData['fields'] as $field) {
            if (!is_array($field) || !isset($field['id'])) {
                continue;
            }
            $key = (string) absint((string) $field['id']);
            $value = $values[$key] ?? '';
            $entry['fields'][(int) $key] = is_array($value)
                ? array_map('sanitize_text_field', $value)
                : sanitize_textarea_field((string) $value);
        }

        // WPForms' native processor performs required-field validation, spam checks,
        // entry persistence, notifications, and the complete hooks.
        try {
            $processor = wpforms()->obj('process');
            if (!$processor || !method_exists($processor, 'process')) {
                return new WP_Error('headless_form_processor_unavailable', 'Form processor unavailable.', ['status' => 503]);
            }

            $_POST['wpforms'] = $entry;
            // WPForms requires its native AJAX action when ajax submission is enabled.
            $_POST['action'] = 'wpforms_submit';
            $_POST['page_url'] = esc_url_raw((string) ($request->get_header('referer') ?: home_url('/')));
            $processor->process($entry);

            if (!empty($processor->errors[$formId])) {
                $fieldErrors = [];
                $generalErrors = [];
                foreach ($processor->errors[$formId] as $fieldId => $error) {
                    $message = is_string($error) ? wp_strip_all_tags($error) : '';
                    if ($message === '') continue;
                    if (in_array((string) $fieldId, ['header', 'footer', 'header_styled', 'footer_styled', 'recaptcha'], true)) {
                        $generalErrors[] = $message;
                    } else {
                        $fieldErrors[(string) $fieldId] = $message;
                    }
                }
                return new WP_REST_Response([
                    'formId' => (string) $formId,
                    'success' => false,
                    'message' => $generalErrors[0] ?? 'Form validation failed.',
                    'fieldErrors' => $fieldErrors,
                ], 422);
            }

            $message = method_exists($processor, 'get_confirmation_message')
                ? wp_strip_all_tags((string) $processor->get_confirmation_message($processor->form_data, $processor->fields, $processor->entry_id))
                : '';

            return new WP_REST_Response([
                'formId' => (string) $formId,
                'success' => true,
                'message' => $message ?: 'Your form has been submitted successfully.',
            ], 200);
        } catch (Throwable) {
            return new WP_Error('headless_form_submission_failed', 'Form submission failed.', ['status' => 500]);
        }
    }
}
