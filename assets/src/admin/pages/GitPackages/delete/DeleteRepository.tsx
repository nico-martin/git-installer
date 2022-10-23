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
  NOTICE_TYPES,
  ShadowBox,
} from '../../../theme';
import { apiDelete } from '../../../utils/apiFetch';
import { VARS } from '../../../utils/constants';
import { IGitPackages } from '../../../utils/types';

const DeleteRepository: React.FC<{
  modal: boolean;
  setModal: (show: boolean) => void;
  repositoryKey: string;
  name: string;
  theme: boolean;
  setRepositories: (
    packages: IGitPackages | ((prevState: IGitPackages) => IGitPackages)
  ) => void;
}> = ({ modal, setModal, repositoryKey, name, theme, setRepositories }) => {
  const [loadingDelete, setLoadingDelete] = React.useState<boolean>(false);
  const [error, setError] = React.useState<string>('');
  const { addToast } = useToast();

  const form = useForm<{ fullDelete: boolean }>({
    defaultValues: {
      fullDelete: true,
    },
  });

  return modal ? (
    <ShadowBox
      title={__('delete Repository', 'shgi')}
      close={() => setModal(false)}
      size="small"
    >
      <p
        dangerouslySetInnerHTML={{
          __html: sprintf(
            __('Are you sure you want to delete the %s "%s"?', 'shgi'),
            theme ? 'theme' : 'plugin',
            '<b>' + name + '</b>'
          ),
        }}
      />
      <Form
        onSubmit={form.handleSubmit((data) => {
          setLoadingDelete(true);
          apiDelete<{
            message: string;
            packages: IGitPackages;
          }>(
            `${
              VARS.restPluginNamespace
            }/git-packages/${repositoryKey}?fullDelete=${
              data.fullDelete ? '1' : '0'
            }`
          )
            .then((resp) => {
              addToast({
                message: resp.message,
                type: NOTICE_TYPES.SUCCESS,
              });
              setRepositories(resp.packages);
            })
            .catch((e) => setError(e))
            .finally(() => setLoadingDelete(false));
        })}
      >
        <FormElement
          form={form}
          name="fullDelete"
          label={__('delete completely', 'shgi')}
          Input={InputCheckbox}
        />
        <p style={{ fontSize: '0.9em' }}>
          {(theme
            ? __('If checked, theme files will also be deleted.', 'shgi')
            : __('If checked, plugin files will also be deleted.', 'shgi')) +
            ' ' +
            __('Otherwise, only the git connection will be removed.', 'shgi')}
        </p>
        {error !== '' && (
          <FormFeedback type={NOTICE_TYPES.ERROR} message={error} />
        )}
        <FormControls
          type="submit"
          loading={loadingDelete}
          value={__('delete Repository', 'shgi')}
          align="right"
        />
      </Form>
    </ShadowBox>
  ) : null;
};

export default DeleteRepository;
