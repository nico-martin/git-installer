import React from 'react';
import { UseFormRegister } from 'react-hook-form';
import cn from '../../utils/classnames';
import { Icon } from '../index';
import styles from './InputText.css';

const InputText = React.forwardRef<
  HTMLInputElement,
  {
    name: string;
    value?: string;
    className?: string;
    classNameInput?: string;
    masked?: boolean;
    disabled?: boolean;
    type?:
      | 'text'
      | 'color'
      | 'date'
      | 'datetime-local'
      | 'email'
      | 'hidden'
      | 'month'
      | 'number'
      | 'password'
      | 'search'
      | 'tel'
      | 'time'
      | 'url'
      | 'week';
    onChange: (value: string) => void;
    prepend: string;
    prependWidth: string;
    [key: string]: any;
  } & ReturnType<UseFormRegister<any>>
>(
  (
    {
      name,
      value: formValue = '',
      className = '',
      classNameInput = '',
      type = 'text',
      masked = false,
      onChange = () => {},
      disabled = false,
      prepend = '',
      prependWidth = null,
      ...props
    },
    ref
  ) => {
    const innerRef = React.useRef(ref);
    const [value, setValue] = React.useState<string>(formValue);
    const [editMode, setEditMode] = React.useState<boolean>(value === '');

    React.useEffect(() => {
      (!masked || editMode) && onChange(value);
    }, [value]);

    const maskedValue = React.useMemo(() => {
      let replaceCount = value.length - 4;
      if (replaceCount <= 0) replaceCount = 0;
      return masked && !editMode
        ? new Array(replaceCount).fill('*').join('') + value.slice(replaceCount)
        : value;
    }, [masked, value, editMode]);

    const { onBlur, onClick, setting, ...otherProps } = props;

    const activateEditMode = () => {
      setEditMode(true);
      // @ts-ignore
      window.setTimeout(() => innerRef?.current?.focus(), 10);
    };

    return (
      <div
        className={cn(className, styles.container)}
        onClick={() => masked && !editMode && activateEditMode()}
        data-prepend={prepend}
      >
        <input
          name={name}
          className={cn(classNameInput, styles.input)}
          style={{
            paddingLeft: `calc(8px + ${
              prependWidth ? prependWidth : prepend.length + 'ch'
            })`,
          }}
          id={name}
          value={maskedValue}
          onChange={(e) => setValue(e.target.value)}
          type={type}
          ref={innerRef}
          disabled={disabled || (masked && !editMode)}
          onBlur={(e) => {
            e.target.value !== '' && setEditMode(false);
            //onBlur(e);
          }}
          {...otherProps}
        />
        {masked && (
          <button
            className={styles.editButton}
            type="button"
            onClick={() => (editMode ? setEditMode(false) : activateEditMode())}
            title="edit"
          >
            <Icon icon={editMode ? 'pencil' : 'pencil-outline'} />
          </button>
        )}
      </div>
    );
  }
);

export default InputText;
