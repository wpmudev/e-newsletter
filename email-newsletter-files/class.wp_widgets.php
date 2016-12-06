<?php
// Widget for Subscribe
class e_newsletter_subscribe extends WP_Widget {
    //constructor
    function __construct() {
        $widget_ops = array( 'description' => __( 'Allow people to subscribe to your newsletter database.') );
        parent::__construct( false, __( 'eNewsletter: Subscribe' ), $widget_ops );
    }

    /** @see WP_Widget::widget */
    function widget( $args, $instance ) {
        global $email_newsletter;

        extract( $args );

        $title = apply_filters( 'widget_title', $instance['title'] );
        $show_name      = $instance['name'];
        $show_groups    = $instance['groups'];
        $subscribe_to_groups = isset($instance['auto_groups']) ? $instance['auto_groups'] : array();

        echo $before_widget;

        if ( $title )
            echo $before_title . $title . $after_title;

        echo $email_newsletter->subscribe_widget($show_name, $show_groups, $subscribe_to_groups);

        echo $after_widget;
    }

    /** @see WP_Widget::update */
    function update( $new_instance, $old_instance ) {
        $instance           = $old_instance;
        $instance['title']  = strip_tags($new_instance['title']);
        $instance['name']   = strip_tags($new_instance['name']);
        $instance['groups'] = strip_tags($new_instance['groups']);
        $instance['auto_groups'] = isset($new_instance['auto_groups']) ? $new_instance['auto_groups'] : array();
        return $instance;
    }

    /** @see WP_Widget::form */
    function form( $instance ) {
        global $email_newsletter;

        if ( isset( $instance['title'] ) )
            $title = esc_attr( $instance['title'] );
        else
            $title = __( 'Subscribe to our Newsletters', 'email-newsletter' );

        if ( isset( $instance['name'] ) )
            $name = esc_attr( $instance['name'] );
        else
            $name = 0;

        if ( isset( $instance['groups'] ) )
            $groups = esc_attr( $instance['groups'] );
        else
            $groups = 0;

        $all_groups = $email_newsletter->get_groups();
        $groups_html = array();
        foreach ($all_groups as $group) {
            $checked = (isset($instance['auto_groups']) && is_array($instance['auto_groups']) && in_array($group['group_id'], $instance['auto_groups'])) ? 'checked="checked"' : '';
            $groups_html[] = '
                <input id="'.$this->get_field_id( 'auto_groups_'.$group['group_id'] ).'" name="'.$this->get_field_name( 'auto_groups' ).'[]" type="checkbox" value="'.$group['group_id'].'" '.$checked.'/>
                <label for="'.$this->get_field_id( 'auto_groups_'.$group['group_id'] ).'">'.$group['group_name'].'</label>
            ';
        }
        ?>
            <p>
                <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'email-newsletter' ) ?></label>
                <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
            </p>
            <p>
                <input id="<?php echo $this->get_field_id( 'name' ); ?>" name="<?php echo $this->get_field_name( 'name' ); ?>" type="checkbox" value="1" <?php echo $name ? ' checked' : '';?> />
                <label for="<?php echo $this->get_field_id( 'name' ); ?>"><?php _e( 'Ask the name?', 'email-newsletter' ) ?></label>
            </p>
            <p>
                <input id="<?php echo $this->get_field_id( 'groups' ); ?>" name="<?php echo $this->get_field_name( 'groups' ); ?>" type="checkbox" value="1" <?php echo $groups ? ' checked' : '';?> />
                <label for="<?php echo $this->get_field_id( 'groups' ); ?>"><?php _e( 'Show Groups?', 'email-newsletter' ) ?></label>
            </p>
            <?php
            if(is_array($all_groups) && count($all_groups) > 0) {
                $groups_html = implode('<br/>', $groups_html)
            ?>
                <p>
                    <?php _e( 'Automatically subscribe to following groups:', 'email-newsletter' ) ?>
                </p>
                <p><?php echo $groups_html; ?></p>

            <?php
            }
    }
} // class e_newsletter_subscribe

add_action( 'widgets_init', create_function( '', 'return register_widget("e_newsletter_subscribe");' ) );