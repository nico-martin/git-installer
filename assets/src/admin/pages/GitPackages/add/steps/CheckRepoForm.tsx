import React from 'react';
import { useForm } from 'react-hook-form';
import { __ } from '@wordpress/i18n';
import {
  Form,
  FormControls,
  FormElement,
  FormFeedback,
  InputText,
  NOTICE_TYPES,
} from '../../../../theme';
import { AddRepositoryFormPropsI } from '../AddRepository';

const CheckRepoForm: React.FC<AddRepositoryFormPropsI> = ({
  promise,
  submit,
  className = '',
}) => {
  const [loading, setLoading] = React.useState<boolean>(false);
  const [error, setError] = React.useState<string>('');
  const form = useForm<{
    repositoryUrl: string;
  }>({
    defaultValues: {
      repositoryUrl: '',
    },
  });

  return (
    <Form
      onSubmit={form.handleSubmit((data) => {
        setLoading(true);
        promise(data.repositoryUrl, null)
          .then()
          .catch((e) => setError(e))
          .finally(() => setLoading(false));
      })}
      className={className}
    >
      <FormElement
        form={form}
        name="repositoryUrl"
        label={__('Repository URL', 'shgi')}
        Input={InputText}
        rules={{
          required: __('Required field', 'shgi'),
          pattern: {
            value: /^(https:\/\/(github|gitlab|bitbucket)\.\S+)/,
            message: __(
              'The URL must lead to a Github, Gitlab or Bitbucket repository',
              'shgi'
            ),
          },
        }}
      />
      {error !== '' && (
        <FormFeedback type={NOTICE_TYPES.ERROR} message={error} />
      )}
      <FormControls
        type="submit"
        loading={loading}
        value={submit}
        align="right"
      />
    </Form>
  );
};

export default CheckRepoForm;
