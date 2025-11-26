<?php

declare(strict_types=1);

namespace Collegeman\CollaborativeTravelJournal\Rest;

use Collegeman\CollaborativeTravelJournal\PostTypes\Trip;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class CollaboratorsController {
    public const NAMESPACE = 'ctj/v1';

    public static function register(): void {
        add_action('rest_api_init', [self::class, 'registerRoutes']);
    }

    public static function registerRoutes(): void {
        register_rest_route(self::NAMESPACE, '/trips/(?P<trip_id>\d+)/collaborators', [
            [
                'methods' => 'GET',
                'callback' => [self::class, 'getCollaborators'],
                'permission_callback' => [self::class, 'canViewTrip'],
                'args' => [
                    'trip_id' => [
                        'validate_callback' => fn($param) => is_numeric($param),
                    ],
                ],
            ],
            [
                'methods' => 'POST',
                'callback' => [self::class, 'inviteCollaborator'],
                'permission_callback' => [self::class, 'canEditTrip'],
                'args' => [
                    'trip_id' => [
                        'validate_callback' => fn($param) => is_numeric($param),
                    ],
                    'email' => [
                        'required' => true,
                        'validate_callback' => fn($param) => is_email($param),
                    ],
                    'role' => [
                        'default' => 'contributor',
                        'validate_callback' => fn($param) => in_array($param, ['contributor', 'viewer']),
                    ],
                ],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/trips/(?P<trip_id>\d+)/collaborators/(?P<user_id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [self::class, 'removeCollaborator'],
            'permission_callback' => [self::class, 'canEditTrip'],
            'args' => [
                'trip_id' => [
                    'validate_callback' => fn($param) => is_numeric($param),
                ],
                'user_id' => [
                    'validate_callback' => fn($param) => is_numeric($param),
                ],
            ],
        ]);
    }

    public static function canViewTrip(WP_REST_Request $request): bool {
        if (!is_user_logged_in()) {
            return false;
        }

        $trip_id = (int) $request->get_param('trip_id');
        $trip = get_post($trip_id);

        if (!$trip || $trip->post_type !== Trip::POST_TYPE) {
            return false;
        }

        $current_user_id = get_current_user_id();

        if ((int) $trip->post_author === $current_user_id) {
            return true;
        }

        $collaborators = get_post_meta($trip_id, 'collaborators', true) ?: [];
        foreach ($collaborators as $collab) {
            if ((int) ($collab['user_id'] ?? 0) === $current_user_id) {
                return true;
            }
        }

        return false;
    }

    public static function canEditTrip(WP_REST_Request $request): bool {
        if (!is_user_logged_in()) {
            return false;
        }

        $trip_id = (int) $request->get_param('trip_id');
        $trip = get_post($trip_id);

        if (!$trip || $trip->post_type !== Trip::POST_TYPE) {
            return false;
        }

        $current_user_id = get_current_user_id();

        if ((int) $trip->post_author === $current_user_id) {
            return true;
        }

        $collaborators = get_post_meta($trip_id, 'collaborators', true) ?: [];
        foreach ($collaborators as $collab) {
            if ((int) ($collab['user_id'] ?? 0) === $current_user_id) {
                return ($collab['role'] ?? '') === 'contributor';
            }
        }

        return false;
    }

    public static function getCollaborators(WP_REST_Request $request): WP_REST_Response {
        $trip_id = (int) $request->get_param('trip_id');
        $trip = get_post($trip_id);

        $owner = get_userdata((int) $trip->post_author);
        $result = [
            [
                'user_id' => (int) $trip->post_author,
                'email' => $owner ? $owner->user_email : '',
                'display_name' => $owner ? $owner->display_name : 'Unknown',
                'role' => 'owner',
            ],
        ];

        $collaborators = get_post_meta($trip_id, 'collaborators', true) ?: [];
        foreach ($collaborators as $collab) {
            $user = get_userdata((int) $collab['user_id']);
            if ($user) {
                $result[] = [
                    'user_id' => (int) $collab['user_id'],
                    'email' => $user->user_email,
                    'display_name' => $user->display_name,
                    'role' => $collab['role'] ?? 'contributor',
                ];
            }
        }

        return new WP_REST_Response($result, 200);
    }

    public static function inviteCollaborator(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $trip_id = (int) $request->get_param('trip_id');
        $email = sanitize_email($request->get_param('email'));
        $role = $request->get_param('role');

        $trip = get_post($trip_id);
        if (!$trip) {
            return new WP_Error('trip_not_found', 'Trip not found', ['status' => 404]);
        }

        $user = get_user_by('email', $email);

        if (!$user) {
            $user_id = self::createAndInviteUser($email, $trip);
            if (is_wp_error($user_id)) {
                return $user_id;
            }
            $user = get_userdata($user_id);
        } else {
            if ((int) $trip->post_author === $user->ID) {
                return new WP_Error('already_owner', 'This user is already the trip owner', ['status' => 400]);
            }

            $collaborators = get_post_meta($trip_id, 'collaborators', true) ?: [];
            foreach ($collaborators as $collab) {
                if ((int) ($collab['user_id'] ?? 0) === $user->ID) {
                    return new WP_Error('already_collaborator', 'This user is already a collaborator', ['status' => 400]);
                }
            }

            self::sendExistingUserNotification($user, $trip);
        }

        $collaborators = get_post_meta($trip_id, 'collaborators', true) ?: [];
        $collaborators[] = [
            'user_id' => $user->ID,
            'role' => $role,
        ];
        update_post_meta($trip_id, 'collaborators', $collaborators);

        return new WP_REST_Response([
            'user_id' => $user->ID,
            'email' => $user->user_email,
            'display_name' => $user->display_name,
            'role' => $role,
        ], 201);
    }

    private static function createAndInviteUser(string $email, \WP_Post $trip): int|WP_Error {
        $username = self::generateUsername($email);
        $password = wp_generate_password(24);

        $user_id = wp_insert_user([
            'user_login' => $username,
            'user_email' => $email,
            'user_pass' => $password,
            'role' => 'author',
        ]);

        if (is_wp_error($user_id)) {
            return $user_id;
        }

        wp_new_user_notification($user_id, null, 'user');

        $inviter = wp_get_current_user();
        $trip_title = $trip->post_title;

        wp_mail(
            $email,
            sprintf('You\'ve been invited to collaborate on "%s"', $trip_title),
            sprintf(
                "Hi!\n\n%s has invited you to collaborate on the trip \"%s\".\n\n" .
                "You should receive a separate email with your login credentials.\n\n" .
                "Once you're logged in, you'll be able to view and contribute to this trip.\n\n" .
                "Happy travels!",
                $inviter->display_name,
                $trip_title
            )
        );

        return $user_id;
    }

    private static function sendExistingUserNotification(\WP_User $user, \WP_Post $trip): void {
        $inviter = wp_get_current_user();
        $trip_title = $trip->post_title;

        wp_mail(
            $user->user_email,
            sprintf('You\'ve been added to the trip "%s"', $trip_title),
            sprintf(
                "Hi %s!\n\n%s has added you as a collaborator on the trip \"%s\".\n\n" .
                "Log in to view and contribute to this trip.\n\n" .
                "Happy travels!",
                $user->display_name,
                $inviter->display_name,
                $trip_title
            )
        );
    }

    private static function generateUsername(string $email): string {
        $base = strstr($email, '@', true);
        $base = sanitize_user($base, true);

        if (empty($base)) {
            $base = 'user';
        }

        $username = $base;
        $counter = 1;

        while (username_exists($username)) {
            $username = $base . $counter;
            $counter++;
        }

        return $username;
    }

    public static function removeCollaborator(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $trip_id = (int) $request->get_param('trip_id');
        $user_id = (int) $request->get_param('user_id');

        $trip = get_post($trip_id);
        if (!$trip) {
            return new WP_Error('trip_not_found', 'Trip not found', ['status' => 404]);
        }

        if ((int) $trip->post_author === $user_id) {
            return new WP_Error('cannot_remove_owner', 'Cannot remove the trip owner', ['status' => 400]);
        }

        $collaborators = get_post_meta($trip_id, 'collaborators', true) ?: [];
        $found = false;

        $collaborators = array_filter($collaborators, function($collab) use ($user_id, &$found) {
            if ((int) ($collab['user_id'] ?? 0) === $user_id) {
                $found = true;
                return false;
            }
            return true;
        });

        if (!$found) {
            return new WP_Error('not_collaborator', 'User is not a collaborator', ['status' => 404]);
        }

        update_post_meta($trip_id, 'collaborators', array_values($collaborators));

        return new WP_REST_Response(['success' => true], 200);
    }
}
