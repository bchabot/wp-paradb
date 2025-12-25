<?php
/**
 * Public witness submission form template
 *
 * @link              https://github.com/bchabot/wp-paradb
 * @since             1.0.0
 * @package           WP_ParaDB
 * @subpackage        WP_ParaDB/public/partials
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if submissions are enabled.
$options = get_option( 'wp_paradb_options', array() );
$allow_submissions = isset( $options['allow_public_submissions'] ) ? $options['allow_public_submissions'] : true;

if ( ! $allow_submissions ) {
	echo '<div class="paradb-notice error">';
	echo '<p>' . esc_html__( 'Witness submissions are currently disabled.', 'wp-paradb' ) . '</p>';
	echo '</div>';
	return;
}

// Check for success/error messages.
$success_message = get_transient( 'paradb_witness_success' );
$error_message = get_transient( 'paradb_witness_error' );

if ( $success_message ) {
	delete_transient( 'paradb_witness_success' );
	echo '<div class="paradb-notice success">';
	echo '<p>' . esc_html( $success_message ) . '</p>';
	echo '</div>';
	return;
}

if ( $error_message ) {
	delete_transient( 'paradb_witness_error' );
	echo '<div class="paradb-notice error">';
	echo '<p>' . esc_html( $error_message ) . '</p>';
	echo '</div>';
}

// Get phenomena types.
$phenomena_types = isset( $options['phenomena_types'] ) ? $options['phenomena_types'] : array();
?>

<div class="paradb-witness-form">
	<div class="form-intro">
		<h2><?php esc_html_e( 'Submit a Witness Account', 'wp-paradb' ); ?></h2>
		<p><?php esc_html_e( 'If you have experienced paranormal activity and would like to share your account with our research team, please fill out the form below. Your information will be kept confidential and reviewed by our investigators.', 'wp-paradb' ); ?></p>
		<p><em><?php esc_html_e( 'You may submit anonymously by leaving the contact information fields blank.', 'wp-paradb' ); ?></em></p>
	</div>

	<form method="post" action="" class="witness-submission-form">
		<?php wp_nonce_field( 'submit_witness_account', 'witness_nonce' ); ?>

		<fieldset class="form-section">
			<legend><?php esc_html_e( 'Your Information (Optional)', 'wp-paradb' ); ?></legend>
			
			<div class="form-field">
				<label for="account_name"><?php esc_html_e( 'Your Name', 'wp-paradb' ); ?></label>
				<input type="text" name="account_name" id="account_name" class="form-control">
				<p class="field-description"><?php esc_html_e( 'Leave blank for anonymous submission', 'wp-paradb' ); ?></p>
			</div>

			<div class="form-field">
				<label for="account_email"><?php esc_html_e( 'Email Address', 'wp-paradb' ); ?></label>
				<input type="email" name="account_email" id="account_email" class="form-control">
				<p class="field-description"><?php esc_html_e( 'We will only use this to contact you about your submission', 'wp-paradb' ); ?></p>
			</div>

			<div class="form-field">
				<label for="account_phone"><?php esc_html_e( 'Phone Number', 'wp-paradb' ); ?></label>
				<input type="tel" name="account_phone" id="account_phone" class="form-control">
			</div>
		</fieldset>

		<fieldset class="form-section">
			<legend><?php esc_html_e( 'Incident Information', 'wp-paradb' ); ?></legend>

			<div class="form-field">
				<label for="incident_date"><?php esc_html_e( 'When did this occur?', 'wp-paradb' ); ?></label>
				<input type="date" name="incident_date" id="incident_date" class="form-control">
			</div>

			<div class="form-field">
				<label for="incident_location"><?php esc_html_e( 'Where did this occur?', 'wp-paradb' ); ?> *</label>
				<input type="text" name="incident_location" id="incident_location" class="form-control" placeholder="<?php esc_attr_e( 'City, State or general location', 'wp-paradb' ); ?>">
				<p class="field-description"><?php esc_html_e( 'You do not need to provide a specific address', 'wp-paradb' ); ?></p>
			</div>

			<?php if ( ! empty( $phenomena_types ) ) : ?>
				<div class="form-field">
					<label for="phenomena_types"><?php esc_html_e( 'Type of Phenomena', 'wp-paradb' ); ?></label>
					<select name="phenomena_types[]" id="phenomena_types" class="form-control">
						<option value=""><?php esc_html_e( 'Select type...', 'wp-paradb' ); ?></option>
						<?php foreach ( $phenomena_types as $phenomenon ) : ?>
							<option value="<?php echo esc_attr( $phenomenon ); ?>"><?php echo esc_html( $phenomenon ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			<?php endif; ?>

			<div class="form-field">
				<label for="incident_description"><?php esc_html_e( 'Please describe what you experienced', 'wp-paradb' ); ?> *</label>
				<textarea name="incident_description" id="incident_description" rows="10" class="form-control" required placeholder="<?php esc_attr_e( 'Provide as much detail as possible about what you witnessed or experienced...', 'wp-paradb' ); ?>"></textarea>
				<p class="field-description"><?php esc_html_e( 'Include details such as what you saw, heard, felt, or otherwise experienced. The more detail you can provide, the better we can investigate.', 'wp-paradb' ); ?></p>
			</div>
		</fieldset>

		<div class="form-field submit-field">
			<button type="submit" name="submit_witness_account" class="submit-button">
				<?php esc_html_e( 'Submit Witness Account', 'wp-paradb' ); ?>
			</button>
		</div>

		<div class="form-privacy-notice">
			<p><small><?php esc_html_e( 'By submitting this form, you acknowledge that your information may be used for research purposes. We respect your privacy and will not share your contact information without your permission.', 'wp-paradb' ); ?></small></p>
		</div>
	</form>
</div>