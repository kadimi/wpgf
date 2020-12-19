<?php
/**
 * WPGF.
 *
 * @package kadimi/wpgf
 */

namespace Kadimi;

class WPGF {

	public static function AddUISetting( string $name, string $title, string $type = 'text', array $options = [], string $for = 'notification' ) {
		$defaults = [
			'tooltip' => null,
			'show_merge_tags' => false,
		];
		$options += $defaults;
		extract( $options );
		if ( $tooltip ) {
			add_filter( 'gform_tooltips', function( $tooltips ) use ( $name, $title, $tooltip, $for ) {
				$tooltips[ "{$for}_{$name}" ] = '<h6>' . $title . '</h6>' . $tooltip;
				return $tooltips;
			} );
		}
		do_action( "gravityforms_before_field_setting_$name" );
		add_filter( "gform_{$for}_ui_settings", function( $ui_settings, $subject, $form ) use( $name, $title, $type, $show_merge_tags, $for ) {
			ob_start();
			?>
				<tr valign="top">
					<th scope="row">
						<label for="<?php echo "gform_{$for}_{$name}"; ?>">
							<?php echo esc_html( $title ); ?>
							<?php gform_tooltip( "{$for}_{$name}" ) ?>
						</label>
					</th>
					<td>
					<?php if ( 'checkbox' === $type ): ?>
						<input
							type="checkbox"
							name="<?php echo "gform_{$for}_{$name}"; ?>"
							id="<?php echo "gform_{$for}_{$name}"; ?>"
							value="1"
							<?php echo empty( $subject[ $name ] ) ? '' : "checked='checked'" ?>
						/>
						<label for="<?php echo "gform_{$for}_{$name}"; ?>" class="inline">
							<?php echo esc_html( $title ); ?>
							<?php gform_tooltip( "{$for}_{$name}" ) ?>
						</label>
					<?php else: ?>
						<input
							type="text"
							name="<?php echo "gform_{$for}_{$name}"; ?>"
							id="<?php echo "gform_{$for}_{$name}"; ?>"
							value="<?php echo esc_attr( rgar( $subject, $name ) ) ?>"
							<?php echo $show_merge_tags ? 'class="merge-tag-support mt-hide_all_fields mt-position-right"' : ''; ?>
							
						/>
					<?php endif; ?>
					</td>
				</tr> <!-- / <?php echo esc_html( $title ); ?> -->
			<?php
			$ui_settings["{$for}_{$name}"] = ob_get_clean();
			return $ui_settings;
		}, 10, 3 );
		add_filter( "gform_pre_{$for}_save", function( $subject ) use ( $name, $type, $for ) {
			$subject[ $name ] = ( 'checkbox' === $type ) ? (bool) rgpost( "gform_{$for}_{$name}" ) : rgpost( "gform_{$for}_{$name}" );
			return $subject;
		} );
		do_action( "gravityforms_after_ui_setting_$name" );
	}

	public static function AddFieldSetting( string $name, string $title, string $type = 'text', array $options = [], string $for = 'advanced' ) {
		$defaults = [
			'dependents' => [],
			'tooltip' => null,
			'position' => - 1,
		];
		$options += $defaults;
		extract( $options );
		if ( $tooltip ) {
			add_filter( 'gform_tooltips', function( $tooltips ) use( $name, $title, $tooltip ) {
				$tooltips[ "form_field_{$name}" ] = '<h6>' . $title . '</h6>' . $tooltip;
				return $tooltips;
			} );
		}
		do_action( "gravityforms_before_field_setting_$name" );
		add_action( "gform_field_{$for}_settings", function( $current_position ) use ( $name, $title, $type, $position ) {
			if ( $position !== $current_position ) {
				return;
			}	
			?>
			<li class="<?php echo "{$name}_field_setting"; ?> field_setting">
			<?php if ( 'checkbox' === $type ) : ?> 
				<input
					type="checkbox"
					id="<?php echo "field_{$name}"; ?>"
					class="<?php echo "field_{$name}"; ?>"
					onclick="SetFieldProperty( '<?php echo "field_{$name}"; ?>', this.checked );"
					onkeypress="SetFieldProperty( '<?php echo "field_{$name}"; ?>', this.checked );"
				/>
				<label for="<?php echo "field_{$name}"; ?>" class="inline">
					<?php echo esc_html( $title ); ?>
					<?php gform_tooltip( "form_field_{$name}" ) ?>
				</label>
			<?php else : ?> 
				<label for="<?php echo "field_{$name}"; ?>" class="section_label">
					<?php echo esc_html( $title ); ?>
					<?php gform_tooltip( "form_field_{$name}" ) ?>
				</label>
				<input
					type="text"
					id="<?php echo "field_{$name}"; ?>"
					class="<?php echo "field_{$name}"; ?>"
					onkeyup="SetFieldProperty( '<?php echo "field_{$name}"; ?>', this.value );"
				/>
			<?php endif; ?> 
			<?php
		} );
		add_action( 'gform_editor_js', function() use( $name, $type, $dependents ) {
			?>
			<script type='text/javascript'>
				jQuery( function( $ ) {
					/**
					 * Add setting to fields.
					 */
					for( field in fieldSettings ) {
						fieldSettings[ field ] += ", .<?php echo "{$name}_field_setting"; ?>";
					}
					/**
					 * Populate settings with existing values.
					 */
					$( document ).on( 'gform_load_field_settings', function( event, field, form ) {
						<?php if ( 'checkbox' === $type ) : ?>
							$( '#<?php echo "field_{$name}"; ?>' ).attr( 'checked', field[ '<?php echo "field_{$name}"; ?>' ] == true );
						<?php else : ?>
							$( '#<?php echo "field_{$name}"; ?>' ).val( field[ '<?php echo "field_{$name}"; ?>' ] );
						<?php endif; ?>
						<?php if ( is_array( $dependents ) && $dependents ) : ?>
							$( '#<?php echo "field_{$name}"; ?>' ).on( 'change', function() {
								<?php foreach ( $dependents as $dependent ) : ?>
									if ( field[ '<?php echo "field_{$name}"; ?>' ] ) {
										$( '.<?php echo $dependent; ?>' ).show( 'slow' );
									} else {
										$( '.<?php echo $dependent; ?>' ).hide( 'slow' );
									}
								<?php endforeach; ?>
							} ).change();
						<?php endif; ?>
					} );
				} );
			</script>
			<?php
		}, 10, 3 );
		do_action( "gravityforms_after_field_setting_$name" );
	}

	public static function GetAdvancedFieldInput( $field, $input_id ) {
		foreach ( $field->inputs as $input ) {
			if ( "{$field->id}.{$input_id}" === $input[ 'id' ] ) {
				return $input;
			}
		}
		return false;
	}
}