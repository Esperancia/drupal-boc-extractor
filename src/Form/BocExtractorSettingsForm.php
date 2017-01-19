<?php

	namespace Drupal\bocExtractor\Form;

	use Drupal\Core\Form\ConfigFormBase;
	use Drupal\Core\Form\FormStateInterface;

	class BocExtractorSettingsForm extends ConfigFormBase {

		/** 
		* {@inheritdoc}
		*/
		public function getFormId() {
		    return 'bocExtractor_admin_settings';
		}

		/** 
		* {@inheritdoc}
		*/
		protected function getEditableConfigNames() {
		    return [
		      'bocExtractor.settings',
		    ];
		}

		/** 
		* {@inheritdoc}
		*/
		public function buildForm(array $form, FormStateInterface $form_state) {
		    $config = $this->config('bocExtractor.settings');

		    $form['BocExtractor_url'] = array(
		      '#type' => 'textfield',
		      '#title' => $this->t('BOCs url'),
		      '#default_value' => $config->get('BocExtractor_url'),
		    );  

		    $form['BocExtractor_class'] = array(
		      '#type' => 'textfield',
		      '#title' => $this->t('BOCs class'),
		      '#default_value' => $config->get('BocExtractor_class'),
		    );  

		    return parent::buildForm($form, $form_state);
		}

		/** 
		* {@inheritdoc}
		*/
		public function submitForm(array &$form, FormStateInterface $form_state) {
		    // Retrieve the configuration
		    $this->config('bocExtractor.settings')
		      // Set the submitted configuration setting
		      ->set('BocExtractor_url', $form_state->getValue('BocExtractor_url'))
		      // You can set multiple configurations at once by making
		      // multiple calls to set()
		      ->set('BocExtractor_class', $form_state->getValue('BocExtractor_class'))
		      ->save();

		    parent::submitForm($form, $form_state);
		}
	}

?>