<?php

/**
 * Adds the Album Screen to the BuddyPress Profile
 *
 * @package BuddyPressMedia
 * @subpackage Profile
 *
 * @author Saurabh Shukla <saurabh.shukla@rtcamp.com>
 * @author Gagandeep Singh <gagandeep.singh@rtcamp.com>
 *
 */
class BPMediaAlbumScreen extends BPMediaScreen {

    var $filters;

    /**
     *
     * @param type $media_type
     * @param type $slug
     */
    public function __construct($media_type, $slug) {
        parent::__construct($media_type, $slug);
    }

    /**
     *
     * @global type $bp
     */
    function screen() {
        global $bp;
        if (isset($bp->action_variables[0])) {
            switch ($bp->action_variables[0]) {
                case BP_MEDIA_ALBUMS_EDIT_SLUG :
                    $this->edit_screen();
                    break;
                case BP_MEDIA_ALBUMS_VIEW_SLUG:
                    $this->entry_screen();
                    $this->template_actions('entry_screen');
                    break;
                case BP_MEDIA_DELETE_SLUG :
                    if (!isset($bp->action_variables[1])) {
                        $this->page_not_exist();
                    }
                    $media_actions = new BPMediaActions();
                    if ( $media_actions->default_user_album() != $bp->action_variables[1])
                        $this->entry_delete();
                    else
                        $this->page_not_exist();
                    break;
                default:
                    $this->set_query();
                    $this->template_actions('screen');
            }
        } else {
            $this->set_query();
            $this->template_actions('screen');
        }
        $this->template->loader();
    }

    /**
     *
     * @global type $bp_media_albums_query
     */

    /**
     *
     * @global type $bp_media_albums_query
     */
    function screen_content() {
        global $bp_media_albums_query;

        $this->hook_before();

        if ($bp_media_albums_query && $bp_media_albums_query->have_posts()):
            echo '<ul id="bp-album-list" class="bp-media-gallery item-list">';
            while ($bp_media_albums_query->have_posts()) : $bp_media_albums_query->the_post();
                $this->template->the_album_content();
            endwhile;
            echo '</ul>';
            $this->template->show_more('albums');
        else:
            BPMediaFunction::show_formatted_error_message(sprintf(__('Sorry, no %s were found.', BP_MEDIA_TXT_DOMAIN), $this->slug), 'info');
        endif;
        $this->hook_after();
    }

    /**
     *
     * @global type $bp
     * @global BPMediaAlbum $bp_media_current_album
     * @return boolean
     */
    function entry_screen() {
        global $bp, $bp_media_current_album;
        if (!$bp->action_variables[0] == BP_MEDIA_ALBUMS_VIEW_SLUG)
            return false;
        try {
            $bp_media_current_album = new BPMediaAlbum($bp->action_variables[1]);
        } catch (Exception $e) {
            /* Send the values to the cookie for page reload display */
            @setcookie('bp-message', $_COOKIE['bp-message'], time() + 60 * 60 * 24, COOKIEPATH);
            @setcookie('bp-message-type', $_COOKIE['bp-message-type'], time() + 60 * 60 * 24, COOKIEPATH);
            $this->template->redirect($this->media_const);
            exit;
        }
    }

    /**
     *
     * @global type $bp
     * @global BPMediaAlbum $bp_media_current_album
     * @global type $bp_media_query
     * @return boolean
     */
    function entry_screen_content() {
        global $bp, $bp_media_current_album, $bp_media_query;
        if (!$bp->action_variables[0] == BP_MEDIA_ALBUMS_VIEW_SLUG)
            return false;
        $allowed_edit = false;
        if (is_user_logged_in()) {
            if (!bp_is_user()) {
                if (bp_is_active('groups')) {
                    if (groups_is_user_admin(bp_loggedin_user_id(), $bp->groups->current_group->id)) {
                        $allowed_edit = true;
                    } elseif (bp_displayed_user_id() == bp_loggedin_user_id()) {
                        $allowed_edit = true;
                    }
                }
            } else {
                if (bp_displayed_user_id() == bp_loggedin_user_id()) {
                    $allowed_edit = true;
                }
            }

            if ($allowed_edit != false) {
                echo '<div class="album-edit">';
                echo '<a href="' . $bp_media_current_album->get_edit_url() . '" class="button item-button bp-secondary-action bp-media-edit bp-media-edit-album" title="' . __('Edit Album', BP_MEDIA_TXT_DOMAIN) . '">' . __('Edit', BP_MEDIA_TXT_DOMAIN) . '</a>';
                $media_actions = new BPMediaActions();
                if ( $media_actions->default_user_album() != $bp_media_current_album->get_id())
                    echo '<a href="' . $bp_media_current_album->get_delete_url() . '" class="button item-button bp-secondary-action delete-activity-single confirm" rel="nofollow">' . __("Delete", BP_MEDIA_TXT_DOMAIN) . '</a>';
                echo '</div>';
            }
        }
        $this->inner_query($bp_media_current_album->get_id());
        $this->hook_before();
        if ($bp_media_current_album && $bp_media_query->have_posts()) {
            if (bp_is_my_profile() || BPMediaGroupLoader::can_upload()) {
                BPMediaUploadScreen::upload_screen_content();
            }
            echo '<ul id="bp-media-list" class="bp-media-gallery albums item-list">';

            while ($bp_media_query->have_posts()) {
                $bp_media_query->the_post();
                $this->template->the_content();
            }
            echo '</ul>';
            $this->template->show_more();
        } else {
            BPMediaFunction::show_formatted_error_message(__('Sorry, no media items were found in this album.', BP_MEDIA_TXT_DOMAIN), 'info');
            if (bp_is_my_profile() || BPMediaGroupLoader::can_upload()) {
                echo '<div class="bp-media-area-allocate"></div>';
                BPMediaUploadScreen::upload_screen_content();
            }
        }
        $this->hook_after();
    }

    function entry_screen_title() {

        global $bp_media_current_album;
        /** @var $bp_media_current_entry BPMediaHostWordpress */
        if (is_object($bp_media_current_album))
            echo $bp_media_current_album->get_title();
    }

    /**
     *
     * @global type $bp
     * @global type $bp_media_albums_query
     */
    function set_query() {
        global $bp, $bp_media_albums_query;
        if (isset($bp->action_variables) && is_array($bp->action_variables) && isset($bp->action_variables[0]) && $bp->action_variables[0] == 'page' && isset($bp->action_variables[1]) && is_numeric($bp->action_variables[1])) {
            $paged = $bp->action_variables[1];
        } else {
            $paged = 1;
        }
        if ($bp->current_action == BP_MEDIA_ALBUMS_SLUG) {
            $query = new BPMediaQuery();
            $args = $query->init('album');
            $bp_media_albums_query = new WP_Query($args);
        }
    }

    /**
     *
     * @global type $bp
     * @global type $bp_media_query
     * @param type $album_id
     */
    function inner_query($album_id = 0) {
        global $bp, $bp_media_query;
        $paged = 0;
        $action_variables = isset($bp->canonical_stack['action_variables']) ? $bp->canonical_stack['action_variables'] : null;
        if (isset($action_variables) && is_array($action_variables) && isset($action_variables[0])) {
            if ($action_variables[0] == 'page' && isset($action_variables[1]) && is_numeric($action_variables[1]))
                $paged = $action_variables[1];
            else if (isset($action_variables[1]) && $action_variables[1] == 'page' && isset($action_variables[2]) && is_numeric($action_variables[2]))
                $paged = $action_variables[2];
        }
        if (!$paged)
            $paged = 1;
        $this->filter_entries();
        if ($bp->current_component == 'groups') {
            $query = new BPMediaQuery();
            $args = $query->init(false, $album_id, false, $paged);
            $bp_media_query = new WP_Query($args);
        }
        if ($bp->current_action == BP_MEDIA_ALBUMS_SLUG) {
            $query = new BPMediaQuery();
            $args = $query->init(false, $album_id, false, $paged);
            $bp_media_query = new WP_Query($args);
        }
    }

    function filter_entries() {
        global $bp_media;
        $enabled = $bp_media->enabled();
        if (isset($enabled['upload']))
            unset($enabled['upload']);
        if (isset($enabled['album']))
            unset($enabled['album']);
        foreach ($enabled as $type => $active) {
            if ($active == true) {
                $filters[] = $type;
            }
        }

        if (count($filters) == 1)
            $filters = $filters[0];
        $this->filters = $filters;
    }

}

?>
