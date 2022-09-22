import React from 'react';
import { useForm } from 'react-hook-form';
import { __ } from '@wordpress/i18n';
import { useToast } from '../../../components/toast/toastContext';
import {
  Form,
  FormControls,
  FormElement,
  FormFeedback,
  InputCheckbox,
  InputSelect,
  InputText,
  NOTICE_TYPES,
} from '../../../theme';
import { apiGet, apiPut } from '../../../utils/apiFetch';
import { VARS } from '../../../utils/constants';
import {
  IGitPackageRaw,
  IGitPackages,
  IGitPackageBranch,
} from '../../../utils/types';

const AddRepositoryForm: React.FC<{
  setRepositories: (packages: IGitPackages) => void;
  onFinish: () => void;
  repository: IGitPackageRaw;
}> = ({ setRepositories, onFinish, repository }) => {
  const [loading, setLoading] = React.useState<boolean>(false);
  const [error, setError] = React.useState<string>('');
  const form = useForm<{
    repositoryUrl: string;
    repositoryIsTheme: boolean;
    activeBranch: string;
  }>({
    defaultValues: {
      repositoryUrl: repository.baseUrl,
      repositoryIsTheme: false,
      activeBranch:
        Object.values(repository.branches).find((branch) => branch.default)
          .name || null,
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
            onFinish();
            addToast({
              message: resp.message,
              type: NOTICE_TYPES.SUCCESS,
            });
            form.setValue('repositoryUrl', '');
          })
          .catch((e) => setError(e))
          .finally(() => {
            setLoading(false);
          });
      })}
    >
      <FormElement
        form={form}
        name="repositoryUrl"
        label={__('Repository URL', 'shgi')}
        Input={InputText}
        disabled
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
      <FormElement
        form={form}
        name="repositoryIsTheme"
        label={__('Install as Theme', 'shgi')}
        Input={InputCheckbox}
      />
      <FormElement
        form={form}
        name="activeBranch"
        label={__('Branch', 'shgi')}
        Input={InputSelect}
        options={Object.values(repository.branches).reduce(
          (acc, branch: IGitPackageBranch) => ({
            ...acc,
            [branch.name]: branch.name,
          }),
          {}
        )}
      />
      {error !== '' && (
        <FormFeedback type={NOTICE_TYPES.ERROR}>{error}</FormFeedback>
      )}
      <FormControls
        type="submit"
        loading={loading}
        value={__('Install', 'shgi')}
      />
    </Form>
  );
};

export default AddRepositoryForm;
