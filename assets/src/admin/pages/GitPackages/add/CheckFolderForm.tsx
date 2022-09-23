import { Message } from 'postcss';
import React from 'react';
import { useForm } from 'react-hook-form';
import { __, sprintf } from '@wordpress/i18n';
import { useToast } from '../../../components/toast/toastContext';
import {
  Form,
  FormControls,
  FormElement,
  FormFeedback,
  InputCheckbox,
  InputSelect,
  InputText,
  Notice,
  NOTICE_TYPES,
} from '../../../theme';
import { apiGet, apiPost, apiPut } from '../../../utils/apiFetch';
import { VARS } from '../../../utils/constants';
import {
  IGitPackageRaw,
  IGitPackages,
  IGitPackageBranch,
  IGitWordPressPackage,
} from '../../../utils/types';

const AddRepositoryForm: React.FC<{
  repository: IGitPackageRaw;
  setRepositories: (packages: IGitPackages) => void;
  onFinish: () => void;
}> = ({ repository, setRepositories, onFinish }) => {
  const [loading, setLoading] = React.useState<boolean>(false);
  const [error, setError] = React.useState<string>('');
  const { addToast } = useToast();

  const form = useForm<{
    repositoryUrl: string;
    activeBranch: string;
  }>({
    defaultValues: {
      repositoryUrl: repository.baseUrl,
      activeBranch:
        Object.values(repository.branches).find((branch) => branch.default)
          .name || null,
    },
  });

  const checkFolder = (data: { repositoryUrl: string; activeBranch: string }) =>
    apiPost<IGitWordPressPackage>(
      VARS.restPluginNamespace + '/git-packages-dir',
      {
        url: data.repositoryUrl,
        branch: data.activeBranch,
      }
    ).then((resp) =>
      apiPut<{ message: string; packages: IGitPackages }>(
        VARS.restPluginNamespace + '/git-packages',
        {
          url: data.repositoryUrl,
          theme: resp.type === 'theme',
          activeBranch: data.activeBranch,
        }
      )
    );

  return (
    <Form
      onSubmit={form.handleSubmit((data) => {
        setLoading(true);
        checkFolder(data)
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
        value={__('Check installation', 'shgi')}
      />
    </Form>
  );
};

export default AddRepositoryForm;
