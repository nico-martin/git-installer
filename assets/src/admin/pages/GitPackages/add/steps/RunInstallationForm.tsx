import React from 'react';
import { useForm } from 'react-hook-form';
import { __, sprintf } from '@wordpress/i18n';
import {
  Form,
  FormControls,
  FormElement,
  FormFeedback,
  InputSelect,
  Loader,
  NOTICE_TYPES,
} from '../../../../theme';
import cn from '../../../../utils/classnames';
import { VARS } from '../../../../utils/constants';
import { AddRepositoryFormPropsI } from '../AddRepository';
import styles from './RunInstallationForm.css';

const RunInstallationForm: React.FC<AddRepositoryFormPropsI> = ({
  promise,
  submit,
  repoData,
  wpPackage,
  className = '',
}) => {
  const [loading, setLoading] = React.useState<boolean>(false);
  const [error, setError] = React.useState<string>('');

  const form = useForm<{ savePluginAs: string }>({
    defaultValues: {
      savePluginAs: '',
    },
  });

  const showMustUseForm = VARS.mustUsePlugins && wpPackage?.type === 'plugin';

  const install = (savePluginAsMU: boolean = false) => {
    setLoading(true);
    promise(savePluginAsMU, null)
      .then()
      .catch((e) => setError(e))
      .finally(() => setLoading(false));
  };

  React.useEffect(() => {
    !showMustUseForm && install(false);
  }, []);

  const desc = <p>Das Theme</p>;

  return loading ? (
    <div className={cn(className, styles.loadingComp)}>
      <p
        dangerouslySetInnerHTML={{
          __html: sprintf(
            __('The %s "%s" is being installed'),
            wpPackage.type === 'plugin' ? 'Plugin' : 'Theme',
            `<b>${wpPackage.name}</b>`
          ),
        }}
      />
      <Loader className={styles.loader} size={3} />
    </div>
  ) : (
    <Form
      onSubmit={form.handleSubmit((data) =>
        install(data.savePluginAs === 'mustUse')
      )}
      className={className}
    >
      {showMustUseForm && (
        <React.Fragment>
          <p
            dangerouslySetInnerHTML={{
              __html: sprintf(
                __(
                  'The plugin "%s" is ready for installation. Now please define whether the plugin should be installed as a "must use plugin" or as a normal plugin.'
                ),
                `<b>${wpPackage.name}</b>`
              ),
            }}
          />
          <FormElement
            form={form}
            name="savePluginAs"
            label={__('Save plugin as', 'shgi')}
            Input={InputSelect}
            options={{
              ['']: __('select..', 'shgi'),
              normal: __('Normal Plugin', 'shgi'),
              mustUse: __('Must Use Plugin', 'shgi'),
            }}
          />
        </React.Fragment>
      )}
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

export default RunInstallationForm;
