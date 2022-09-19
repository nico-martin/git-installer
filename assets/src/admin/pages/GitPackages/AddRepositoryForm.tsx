import React from 'react';
import { useForm } from 'react-hook-form';
import { __, sprintf } from '@wordpress/i18n';
import { useToast } from '../../components/toast/toastContext';
import {
  Form,
  FormControls,
  FormElement,
  FormFeedback,
  InputCheckbox,
  InputText,
  NOTICE_TYPES,
} from '../../theme';
import { apiPut } from '../../utils/apiFetch';
import { VARS } from '../../utils/constants';
import { IGitPackages } from '../../utils/types';

const AddRepositoryForm = ({
  setRepositories,
}: {
  setRepositories: (packages: IGitPackages) => void;
}) => {
  const [loading, setLoading] = React.useState<boolean>(false);
  const form = useForm<{
    repositoryUrl: string;
    repositoryIsTheme: boolean;
  }>({
    defaultValues: {
      repositoryUrl: '',
      repositoryIsTheme: false,
    },
  });
  const { addToast } = useToast();

  return (
    <Form
      onSubmit={form.handleSubmit((data) => {
        setLoading(true);
        apiPut<{ message: string; packages: IGitPackages }>(
          VARS.restPluginNamespace + '/git-packages',
          {
            url: data.repositoryUrl,
            theme: data.repositoryIsTheme,
          }
        )
          .then((resp) => {
            setRepositories(resp.packages);
            addToast({
              message: resp.message,
              type: NOTICE_TYPES.SUCCESS,
            });
            form.setValue('repositoryUrl', '');
          })
          .catch((e) => {
            addToast({
              message: e,
              type: NOTICE_TYPES.ERROR,
            });
          })
          .finally(() => {
            setLoading(false);
          });
      })}
    >
      <FormElement
        form={form}
        name="repositoryUrl"
        label={__('Repository URL', 'shgu')}
        Input={InputText}
        rules={{
          required: __('Das ist ein Pflichtfeld', 'shgu'),
          pattern: {
            value: /^(https:\/\/(github|gitlab|bitbucket)\.\S+)/,
            message: __(
              'Die URL muss zu einem Github, Gitlab oder Bitbucket Repository führen',
              'shgu'
            ),
          },
        }}
      />
      <FormElement
        form={form}
        name="repositoryIsTheme"
        label={__('Als Theme installieren', 'shgu')}
        Input={InputCheckbox}
      />
      <FormControls type="submit" loading={loading} />
    </Form>
  );
};

export default AddRepositoryForm;
