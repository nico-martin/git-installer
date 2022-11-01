import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { useToast } from '../../../components/toast/toastContext';
import { NOTICE_TYPES } from '../../../theme';
import { apiGet, apiPost, apiPut } from '../../../utils/apiFetch';
import cn from '../../../utils/classnames';
import { VARS } from '../../../utils/constants';
import {
  IGitPackageRaw,
  IGitPackages,
  IGitWordPressPackage,
} from '../../../utils/types';
import styles from './AddRepository.css';
import CheckFolderForm from './steps/CheckFolderForm';
import CheckRepoForm from './steps/CheckRepoForm';
import RunInstallationForm from './steps/RunInstallationForm';

export interface AddRepositoryFormPropsI {
  promise: (a: string | boolean, b?: string) => Promise<boolean>;
  submit: string;
  repoData: IGitPackageRaw;
  wpPackage: IGitWordPressPackage;
  className?: string;
}

interface StepI {
  title: string;
  Form: React.FC<AddRepositoryFormPropsI>;
  promise: (a: string | boolean, b?: string) => Promise<boolean>;
  submit: string;
}

const AddRepository: React.FC<{
  className?: string;
  repositoryKeys: Array<string>;
  setRepositories: (packages: IGitPackages) => void;
  onFinish: () => void;
}> = ({ className = '', repositoryKeys, setRepositories, onFinish = null }) => {
  const [repositoryUrl, setRepositoryUrl] = React.useState<string>(null);
  const [repoData, setRepoData] = React.useState<IGitPackageRaw>(null);
  const [activeBranch, setActiveBranch] = React.useState<string>(null);
  const [dir, setDir] = React.useState<string>(null);
  const [wpPackage, setWpPackage] = React.useState<IGitWordPressPackage>(null);
  const { addToast } = useToast();

  const steps: Array<StepI> = [
    {
      title: __('Repository', ''),
      Form: CheckRepoForm,
      promise: (repositoryUrl) =>
        new Promise<boolean>((resolve, reject) =>
          apiGet<IGitPackageRaw>(
            VARS.restPluginNamespace +
              '/git-packages-check/' +
              btoa(repositoryUrl.toString())
          )
            .then((resp) => {
              if (repositoryKeys.indexOf(resp.key) !== -1) {
                reject(__('The package has already been installed', 'shgi'));
              } else {
                setRepositoryUrl(repositoryUrl.toString());
                setRepoData(resp);
                resolve(true);
              }
            })
            .catch(reject)
        ),
      submit: __('Check URL', 'shgi'),
    },
    {
      title: __('Branch', 'shgi'),
      Form: CheckFolderForm,
      promise: (activeBranch, dir) =>
        new Promise((resolve, reject) =>
          apiPost<IGitWordPressPackage>(
            VARS.restPluginNamespace + '/git-packages-dir',
            {
              url: repositoryUrl,
              branch: activeBranch,
              dir,
            }
          )
            .then((resp) => {
              setActiveBranch(activeBranch.toString());
              setDir(dir);
              setWpPackage(resp);
              resolve(true);
            })
            .catch(reject)
        ),
      submit: __('Check branch', 'shgi'),
    },
    {
      title: __('Install', 'shgi'),
      Form: RunInstallationForm,
      promise: (saveAsMustUsePlugin) =>
        new Promise((resolve, reject) =>
          apiPut<{ message: string; packages: IGitPackages }>(
            VARS.restPluginNamespace + '/git-packages',
            {
              url: repositoryUrl,
              theme: wpPackage.type === 'theme',
              saveAsMustUsePlugin: VARS.mustUsePlugins && saveAsMustUsePlugin,
              activeBranch,
              dir,
              headersFile: wpPackage.headersFile,
            }
          )
            .then((resp) => {
              setRepositories(resp.packages);
              onFinish();
              addToast({
                message: resp.message,
                type: NOTICE_TYPES.SUCCESS,
              });
              resolve(true);
            })
            .catch(reject)
        ),
      submit: __('Run installation', 'shgi'),
    },
  ];

  const activeStepI: number = React.useMemo(
    () => (activeBranch ? 2 : repositoryUrl ? 1 : 0),
    [repoData, wpPackage]
  );

  const activeStep: StepI = React.useMemo(
    () => steps[activeStepI],
    [steps, activeStepI]
  );

  return (
    <div className={className}>
      <div className={styles.stepHeading}>
        {steps.map((step, stepI) => (
          <div
            key={stepI}
            className={cn(styles.stepHeadingElement, {
              [styles.stepHeadingElementVisited]: stepI <= activeStepI,
              [styles.stepHeadingElementActive]: stepI === activeStepI,
            })}
            style={{ width: 100 / steps.length + '%' }}
          >
            <p className={styles.stepHeadingElementTitle}>
              {stepI + 1}. {step.title}
            </p>
          </div>
        ))}
      </div>
      <activeStep.Form
        promise={activeStep.promise}
        submit={activeStep.submit}
        repoData={repoData}
        wpPackage={wpPackage}
        className={styles.stepForm}
      />
    </div>
  );
};

export default AddRepository;
