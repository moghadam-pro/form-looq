<?php /** Footer. @package Formlooq_Theme */ $c = formlooq_content(); ?>
<footer class="site-footer">
	<div class="looq-container">
		<div class="footer-grid">
			<div class="footer-brand"><a class="brand" href="<?php echo esc_url( formlooq_url() ); ?>"><img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/logo-mark.svg' ); ?>" alt=""><span class="brand-logotype"><?php echo esc_html( formlooq_is_fa() ? 'فرم‌لوک' : 'Form LOOQ' ); ?></span></a><p><?php echo esc_html( formlooq_is_fa() ? 'فرم‌ساز متن‌باز، سبک و طراحی‌محور برای وردپرس؛ ساخته‌شده برای RTL و LTR.' : 'An open-source, lightweight, design-first WordPress form builder made for RTL and LTR.' ); ?></p></div>
			<div class="footer-column"><h2><?php echo esc_html( formlooq_is_fa() ? 'محصول' : 'Product' ); ?></h2><a href="<?php echo esc_url( formlooq_url( 'features' ) ); ?>"><?php echo esc_html( $c['nav']['features'] ); ?></a><a href="<?php echo esc_url( formlooq_url( 'demos' ) ); ?>"><?php echo esc_html( $c['nav']['demos'] ); ?></a><a href="<?php echo esc_url( formlooq_url( 'download' ) ); ?>"><?php echo esc_html( $c['nav']['download'] ); ?></a><a href="<?php echo esc_url( formlooq_url( 'changelog' ) ); ?>"><?php echo esc_html( formlooq_is_fa() ? 'تاریخچه تغییرات' : 'Changelog' ); ?></a></div>
			<div class="footer-column"><h2><?php echo esc_html( formlooq_is_fa() ? 'منابع' : 'Resources' ); ?></h2><a href="<?php echo esc_url( formlooq_url( 'docs' ) ); ?>"><?php echo esc_html( $c['nav']['docs'] ); ?></a><a href="<?php echo esc_url( formlooq_url( 'support' ) ); ?>"><?php echo esc_html( formlooq_is_fa() ? 'پشتیبانی' : 'Support' ); ?></a><a href="https://github.com/moghadam-pro/form-looq">GitHub</a><a href="https://github.com/moghadam-pro/form-looq/security/policy"><?php echo esc_html( formlooq_is_fa() ? 'امنیت' : 'Security' ); ?></a></div>
			<div class="footer-column"><h2><?php echo esc_html( formlooq_is_fa() ? 'پروژه' : 'Project' ); ?></h2><a href="<?php echo esc_url( formlooq_url( 'about' ) ); ?>"><?php echo esc_html( formlooq_is_fa() ? 'درباره' : 'About' ); ?></a><a href="<?php echo esc_url( formlooq_url( 'privacy' ) ); ?>"><?php echo esc_html( formlooq_is_fa() ? 'حریم خصوصی' : 'Privacy' ); ?></a><a href="<?php echo esc_url( formlooq_url( 'terms' ) ); ?>"><?php echo esc_html( formlooq_is_fa() ? 'شرایط استفاده' : 'Terms' ); ?></a><a href="https://github.com/moghadam-pro/form-looq/blob/main/LICENSE">GPLv2+</a></div>
		</div>
		<div class="footer-bottom"><span>© <?php echo esc_html( gmdate( 'Y' ) ); ?> Form LOOQ.</span><span><?php echo esc_html( formlooq_is_fa() ? 'ساخته‌شده مستقل و شفاف.' : 'Independently built in the open.' ); ?></span></div>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
