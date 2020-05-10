<?php
	
namespace UKMNorge\Slack;
use stdClass;


class BuildJSON {
	
		public static function response( $response ) {
			$data = new stdClass();
			$data->response_type = $response->getResponseType();
# IKKE-EKS?	$data->fallback = $response->getNavn();
			$data->text = $response->getText();
			$data->attachments = BuildJSON::attachments( $response->getAttachments() );
					
			return json_encode( $data );

		}
	
		public static function field( $field ) {
			$data = new stdClass();
			$data->title = $field->getTitle();
			$data->value = $field->getValue();
			$data->short = $field->getShort();
			return $data;
		}
		
		public static function optionGroup( $optionGroup ) {
			$data = new stdClass();
			$data->text	= $optionGroup->getName();
			$data->options = [];
			foreach( $optionGroup->getOptions() as $option ) {
				$data->options[] = self::option( $option );
			}
			return $data;
		}
		
		public static function option( $option ) {
			$data = new stdClass();
			$data->text = $option->getText();
			$data->value = $option->getValue();
			return $data;
		}
		
		public static function action( $action ) {
			$data = new stdClass();
			$data->name = $action->getName();
			$data->text = $action->getText();
			$data->type = $action->getType();
			
			if( $action->hasOptionGroups() ) {
				$data->option_groups = [];
				foreach( $action->getOptionGroups() as $optionGroup ) {
					$data->option_groups[] = self::optionGroup( $optionGroup );
				}
			}
			
			return $data;
		}
	
		public static function attachments( $attachments ) {
			$data = [];
			
			foreach( $attachments as $attachment ) {
				
				$data_attachment	 				= new stdClass();
				$data_attachment->text	 			= $attachment->getText();
				$data_attachment->fallback	 		= $attachment->getFallback();
				$data_attachment->color 			= $attachment->getColor();
				$data_attachment->attachment_type	= $attachment->getType();
				$data_attachment->callback_id		= $attachment->getCallbackId();
				
				// FIELDS
				if( $attachment->hasFields() ) {
					$data_attachment->fields		= [];
					foreach( $attachment->getFields() as $field ) {
						$data_attachment->fields[] = self::field( $field );
					}
				}
				
				// ACTIONS
				if( $attachment->hasActions() ) {
					$data_attachment->actions		= [];
					foreach( $attachment->getActions() as $action ) {
						$data_attachment->actions[] = self::action( $action );
					}
				}
				
				// ADD ATTACHMENT TO ATTACHMENTS
				$data[] = $data_attachment;
			}
			
			return $data;
		}
	}
